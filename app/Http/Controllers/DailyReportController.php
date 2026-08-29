<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyReportRequest;
use App\Http\Requests\UpdateDailyReportRequest;
use App\Models\Area;
use App\Models\DailyReport;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use App\Services\DailyReportService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class DailyReportController extends Controller
{
    public function __construct(private DailyReportService $service) {}

    public function index(Request $request)
    {
        $date = $this->resolveDate($request->query('date'));

        $reporters = User::whereIn('role', ['spv', 'kabag', 'manager'])
            ->with(['department', 'areaAssignments' => function ($q) use ($date) {
                $q->activeOn($date)->with('area');
            }])
            ->orderBy('name')
            ->get();

        $reports = DailyReport::whereDate('report_date', $date)->get();
        $processedIds = $reports->pluck('reported_by');

        // Sum active area assignments per user, fallback to 1 if no assignment
        $expectedCount = 0;
        foreach ($reporters as $reporter) {
            $activeCount = $reporter->areaAssignments->count();
            $expectedCount += ($activeCount > 0) ? $activeCount : 1;
        }

        $processedCount = $reports->count();

        $grouped = $reporters->groupBy(fn ($user) => $user->department?->name ?? 'Unassigned');

        $summary = [
            'expected' => $expectedCount,
            'processed' => $processedCount,
            'remaining' => max(0, $expectedCount - $processedCount),
            'open' => WorkItem::whereIn('status', ['not_started', 'in_progress'])->count(),
            'blocked' => WorkItem::where('status', 'blocked')->count(),
            'overdue' => WorkItem::whereNotIn('status', ['completed', 'cancelled'])
                ->whereDate('planned_end_date', '<', $date)
                ->count(),
        ];

        // Count daily work items / plans for each reporter on selected date (single aggregate query, no N+1)
        $planCounts = WorkItem::whereDate('planned_start_date', '<=', $date)
            ->whereDate('planned_end_date', '>=', $date)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('owner_id, count(*) as count')
            ->groupBy('owner_id')
            ->pluck('count', 'owner_id');

        return view('daily-reports.index', compact('date', 'grouped', 'processedIds', 'reports', 'summary', 'planCounts'));
    }

    public function getDailyReportOptions(Request $request, User $user)
    {
        $date = $this->resolveDate($request->query('date'));

        $activeAreas = Area::whereIn('id', function ($query) use ($user, $date) {
            $query->select('area_id')
                ->from('area_assignments')
                ->where('user_id', $user->id)
                ->where('started_at', '<=', $date)
                ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $date));
        })->where('is_active', true)->get();

        $hasActiveAssignments = $activeAreas->isNotEmpty();
        if ($activeAreas->isEmpty()) {
            $activeAreas = Area::where('is_active', true)->get();
        }

        $carbonDate = Carbon::parse($date);
        $weekStart = $carbonDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weeklyPlans = WeeklyPlan::where('user_id', $user->id)
            ->whereDate('week_start_date', $weekStart)
            ->get();

        // Check if an existing report exists for this user and date
        $existingReport = DailyReport::where('reported_by', $user->id)
            ->whereDate('report_date', $date)
            ->with(['workItems', 'area'])
            ->first();

        $existingReportData = null;
        if ($existingReport) {
            $existingReportData = [
                'id' => $existingReport->id,
                'report_date' => $existingReport->report_date->toDateString(),
                'area_id' => $existingReport->area_id,
                'area_name' => $existingReport->area?->name ?? ($existingReport->area_id ? 'Area #' . $existingReport->area_id : 'Tanpa Area'),
                'today_result' => $existingReport->today_result,
                'edit_url' => route('daily-reports.edit', $existingReport),
                'update_url' => route('daily-reports.update', $existingReport),
                'work_items' => $existingReport->workItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'description' => $item->description,
                        'weekly_plan_id' => $item->weekly_plan_id,
                        'planned_start_date' => $item->planned_start_date?->toDateString(),
                        'planned_end_date' => $item->planned_end_date?->toDateString(),
                        'proof_of_work_url' => $item->proof_of_work_url,
                        'status' => $item->status->value,
                    ];
                })->values(),
            ];
        }

        return response()->json([
            'areas' => $activeAreas->map(fn($a) => ['id' => $a->id, 'name' => $a->name]),
            'has_active_assignments' => $hasActiveAssignments,
            'weekly_plans' => $weeklyPlans->map(fn($p) => ['id' => $p->id, 'title' => $p->title]),
            'existing_report' => $existingReportData,
        ]);
    }

    public function navigate(Request $request)
    {
        $date = $this->resolveDate($request->query('date'));
        $personId = $request->query('person');
        $areaId = $request->query('area_id');

        if ($personId) {
            $query = DailyReport::where('reported_by', $personId)
                ->whereDate('report_date', $date);

            if ($areaId) {
                $query->where('area_id', $areaId);
            }

            $report = $query->first();

            if (! $report && $areaId) {
                $report = DailyReport::where('reported_by', $personId)
                    ->whereDate('report_date', $date)
                    ->first();
            }

            if ($report) {
                return redirect()->route('daily-reports.edit', $report);
            }

            return redirect()->route('daily-reports.create', [
                'person' => $personId,
                'date' => $date,
                'area_id' => $areaId,
            ]);
        }

        return redirect()->route('daily-reports.index', ['date' => $date]);
    }

    public function create(Request $request)
    {
        $date = $this->resolveDate($request->query('date'));
        $personId = $request->query('person');
        $areaId = $request->query('area_id');

        $person = null;
        if ($personId) {
            $person = User::findOrFail($personId);

            $existing = DailyReport::where('reported_by', $personId)
                ->whereDate('report_date', $date)
                ->where('area_id', $areaId)
                ->first();

            if ($existing) {
                return redirect()->route('daily-reports.edit', $existing);
            }
        }

        return $this->renderEntry($person, $date, null, $areaId);
    }

    public function edit(DailyReport $report)
    {
        return $this->renderEntry($report->reporter, $report->report_date->toDateString(), $report, $report->area_id);
    }

    public function store(StoreDailyReportRequest $request)
    {
        $person = User::findOrFail($request->reported_by);
        $area = Area::findOrFail($request->area_id);

        $existing = DailyReport::where('reported_by', $request->reported_by)
            ->where('area_id', $request->area_id)
            ->whereDate('report_date', $request->report_date)
            ->first();

        $data = $request->validated();
        $data['department_id'] = $area->department_id ?? $person->department_id;
        $data['work_items'] = $request->input('work_items', []);

        if ($existing) {
            if (empty($data['today_result']) && !empty($existing->today_result)) {
                $data['today_result'] = $existing->today_result;
            }
            $report = $this->service->update($existing, $data, auth()->id());
            return redirect()->route('daily-reports.index', ['date' => $report->report_date->toDateString()])
                ->with('status', 'Pekerjaan baru berhasil ditambahkan ke laporan harian.');
        }

        try {
            $report = $this->service->store($data, auth()->id());
        } catch (QueryException $e) {
            $existing = DailyReport::where('reported_by', $request->reported_by)
                ->where('area_id', $request->area_id)
                ->whereDate('report_date', $request->report_date)
                ->first();

            if ($existing) {
                if (empty($data['today_result']) && !empty($existing->today_result)) {
                    $data['today_result'] = $existing->today_result;
                }
                $report = $this->service->update($existing, $data, auth()->id());
                return redirect()->route('daily-reports.index', ['date' => $report->report_date->toDateString()])
                    ->with('status', 'Pekerjaan baru berhasil ditambahkan ke laporan harian.');
            }

            throw $e;
        }

        return redirect()->route('daily-reports.index', ['date' => $report->report_date->toDateString()])
            ->with('status', 'Laporan harian disimpan.');
    }

    public function update(UpdateDailyReportRequest $request, DailyReport $report)
    {
        $data = $request->validated();
        $data['work_items'] = $request->input('work_items', []);

        // Also fetch area/dept mapping from existing report context if not explicitly in request
        $data['reported_by'] = $report->reported_by;
        $data['area_id'] = $report->area_id;
        $data['department_id'] = $report->department_id;

        $this->service->update($report, $data, auth()->id());

        return redirect()->route('daily-reports.index', ['date' => $report->report_date->toDateString()])
            ->with('status', 'Laporan harian diperbarui.');
    }

    private function renderEntry(?User $person, string $date, ?DailyReport $report, ?int $areaId = null)
    {
        $workItems = $person ? $this->loadActiveWorkItems($person->id, $date) : ['overdue' => [], 'current' => [], 'future' => []];
        $defaultDate = $date;

        if ($person) {
            $activeAreas = Area::whereIn('id', function ($query) use ($person, $date) {
                $query->select('area_id')
                    ->from('area_assignments')
                    ->where('user_id', $person->id)
                    ->where('started_at', '<=', $date)
                    ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $date));
            })->where('is_active', true)->get();

            if ($activeAreas->isEmpty()) {
                $activeAreas = Area::where('is_active', true)->get();
            }

            $carbonDate = Carbon::parse($date);
            $weekStart = $carbonDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            $weeklyPlans = WeeklyPlan::where('user_id', $person->id)
                ->whereDate('week_start_date', $weekStart)
                ->get();
        } else {
            $activeAreas = Area::where('is_active', true)->get();
            $weeklyPlans = collect();
        }

        $allPersonnel = User::operationalPersonnel()->orderBy('name')->get();
        $reportItems = $report ? $report->workItems : collect();
        $view = $report ? 'daily-reports.edit' : 'daily-reports.create';

        return view($view, compact('person', 'date', 'workItems', 'report', 'defaultDate', 'activeAreas', 'weeklyPlans', 'areaId', 'allPersonnel', 'reportItems'));
    }

    private function loadActiveWorkItems(int $personId, string $date): array
    {
        $items = WorkItem::where('owner_id', $personId)
            ->whereIn('status', ['not_started', 'in_progress', 'blocked'])
            ->orderBy('planned_start_date')
            ->get();

        $overdue = [];
        $current = [];
        $future = [];

        foreach ($items as $item) {
            $start = $item->planned_start_date->toDateString();
            $end = $item->planned_end_date->toDateString();

            if ($end < $date) {
                $overdue[] = $item;
            } elseif ($start > $date) {
                $future[] = $item;
            } else {
                $current[] = $item;
            }
        }

        return compact('overdue', 'current', 'future');
    }

    private function resolveDate(?string $value): string
    {
        if ($value) {
            try {
                return Carbon::parse($value)->toDateString();
            } catch (\Exception) {
                // fall through to today
            }
        }

        return now()->toDateString();
    }
}
