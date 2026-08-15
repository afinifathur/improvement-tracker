<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyReportRequest;
use App\Http\Requests\UpdateDailyReportRequest;
use App\Models\Area;
use App\Models\DailyReport;
use App\Models\User;
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
            ->with('department')
            ->orderBy('name')
            ->get();

        $processedIds = DailyReport::whereDate('report_date', $date)->pluck('reported_by');

        $grouped = $reporters->groupBy(fn ($user) => $user->department?->name ?? 'Unassigned');

        $summary = [
            'expected' => $reporters->count(),
            'processed' => $processedIds->count(),
            'remaining' => max(0, $reporters->count() - $processedIds->count()),
            'open' => WorkItem::whereIn('status', ['not_started', 'in_progress'])->count(),
            'blocked' => WorkItem::where('status', 'blocked')->count(),
            'overdue' => WorkItem::whereNotIn('status', ['completed', 'cancelled'])
                ->whereDate('planned_end_date', '<', $date)
                ->count(),
        ];

        return view('daily-reports.index', compact('date', 'grouped', 'processedIds', 'summary'));
    }

    public function create(Request $request)
    {
        $date = $this->resolveDate($request->query('date'));
        $personId = $request->query('person');

        if (! $personId) {
            return redirect()->route('daily-reports.index', ['date' => $date]);
        }

        $person = User::findOrFail($personId);

        $existing = DailyReport::where('reported_by', $personId)
            ->whereDate('report_date', $date)
            ->first();

        if ($existing) {
            return redirect()->route('daily-reports.edit', $existing);
        }

        return $this->renderEntry($person, $date, null);
    }

    public function edit(DailyReport $report)
    {
        return $this->renderEntry($report->reporter, $report->report_date->toDateString(), $report);
    }

    public function store(StoreDailyReportRequest $request)
    {
        $person = User::findOrFail($request->reported_by);
        $area = Area::findOrFail($request->area_id);

        $existing = DailyReport::where('reported_by', $request->reported_by)
            ->where('area_id', $request->area_id)
            ->whereDate('report_date', $request->report_date)
            ->first();

        if ($existing) {
            return redirect()->route('daily-reports.edit', $existing)
                ->with('status', 'A daily report for this person, area and date already exists.');
        }

        $data = $request->validated();
        $data['department_id'] = $area->department_id ?? $person->department_id;
        $data['work_items'] = $request->input('work_items', []);

        try {
            $report = $this->service->store($data, auth()->id());
        } catch (QueryException $e) {
            $existing = DailyReport::where('reported_by', $request->reported_by)
                ->where('area_id', $request->area_id)
                ->whereDate('report_date', $request->report_date)
                ->first();

            if ($existing) {
                return redirect()->route('daily-reports.edit', $existing)
                    ->with('status', 'A daily report for this person, area and date already exists.');
            }

            throw $e;
        }

        return redirect()->route('daily-reports.index', ['date' => $report->report_date->toDateString()])
            ->with('status', 'Daily report saved.');
    }

    public function update(UpdateDailyReportRequest $request, DailyReport $report)
    {
        $data = $request->validated();
        $data['work_items'] = $request->input('work_items', []);

        $this->service->update($report, $data, auth()->id());

        return redirect()->route('daily-reports.index', ['date' => $report->report_date->toDateString()])
            ->with('status', 'Daily report updated.');
    }

    private function renderEntry(User $person, string $date, ?DailyReport $report)
    {
        $workItems = $this->loadActiveWorkItems($person->id, $date);
        $defaultDate = Carbon::parse($date)->addDay()->toDateString();

        $view = $report ? 'daily-reports.edit' : 'daily-reports.create';

        return view($view, compact('person', 'date', 'workItems', 'report', 'defaultDate'));
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
