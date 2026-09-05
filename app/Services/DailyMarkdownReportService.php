<?php

namespace App\Services;

use App\Enums\WorkItemStatus;
use App\Models\Area;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\Issue;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use App\Models\WorkItemScheduleChange;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class DailyMarkdownReportService
{
    public function __construct(
        private ComplianceService $complianceService,
        private WorkingDayService $workingDayService
    ) {}

    /**
     * Generate daily markdown snapshot for a business date.
     *
     * @param  Carbon|string|null  $date
     * @param  bool  $force
     * @return array{
     *     status: 'created'|'overwritten'|'exists',
     *     date: string,
     *     mode: string,
     *     file_path: string,
     *     message: string,
     *     content: string
     * }
     */
    public function generate(Carbon|string|null $date = null, bool $force = false): array
    {
        $day = $this->resolveDate($date);
        $dateStr = $day->toDateString();

        $todayStr = Carbon::now('Asia/Jakarta')->toDateString();
        $mode = ($dateStr === $todayStr) ? 'DAILY' : 'RETROACTIVE_RECONSTRUCTION';

        $reportsDir = storage_path('app/reports');
        if (! File::exists($reportsDir)) {
            File::makeDirectory($reportsDir, 0755, true);
        }

        $filePath = $reportsDir . DIRECTORY_SEPARATOR . "{$dateStr}.md";

        if (File::exists($filePath) && ! $force) {
            return [
                'status' => 'exists',
                'date' => $dateStr,
                'mode' => $mode,
                'file_path' => $filePath,
                'message' => "Snapshot {$dateStr}.md already exists. Use --force to regenerate.",
                'content' => File::get($filePath),
            ];
        }

        $statusType = File::exists($filePath) ? 'overwritten' : 'created';

        // 1. Snapshot Metadata
        $metadata = $this->buildMetadata($day, $mode);

        // 2. Compliance Evaluation
        $compliance = $this->complianceService->evaluateDailyCompliance($day);

        // 3. Work Item Status Registers & Counts
        $openStatuses = ['not_started', 'in_progress', 'blocked'];

        $allWorkItems = WorkItem::with(['owner', 'department', 'area', 'weeklyPlan'])
            ->get();

        $activeWorkItems = $allWorkItems->filter(function (WorkItem $item) use ($openStatuses) {
            $statusVal = $item->status instanceof WorkItemStatus ? $item->status->value : (string) $item->status;
            return in_array($statusVal, $openStatuses);
        });

        // Classified Registers
        $overdueItems = $activeWorkItems->filter(function (WorkItem $item) use ($day) {
            return WorkingDayService::isOverdueOn($item->planned_end_date, $day);
        })->sortBy([
            ['planned_end_date', 'asc'],
            ['id', 'asc'],
        ])->values()->map(function (WorkItem $item) use ($day) {
            $end = $item->planned_end_date ? Carbon::parse($item->planned_end_date)->startOfDay() : null;
            $item->days_overdue = $end ? (int) $end->diffInDays($day) : 0;
            return $item;
        });

        $graceItems = $activeWorkItems->filter(function (WorkItem $item) use ($day, $dateStr) {
            $endStr = $item->planned_end_date ? $item->planned_end_date->toDateString() : null;
            return $endStr && $endStr < $dateStr && ! WorkingDayService::isOverdueOn($item->planned_end_date, $day);
        })->sortBy([
            ['planned_end_date', 'asc'],
            ['id', 'asc'],
        ])->values()->map(function (WorkItem $item) use ($day) {
            $end = Carbon::parse($item->planned_end_date)->startOfDay();
            $calendarDiff = (int) $end->diffInDays($day);
            $item->grace_label = $calendarDiff <= 1 ? 'Grace Day 1' : 'Grace Day 2';
            return $item;
        });

        $completedTodayItems = $allWorkItems->filter(function (WorkItem $item) use ($dateStr) {
            $statusVal = $item->status instanceof WorkItemStatus ? $item->status->value : (string) $item->status;
            return $statusVal === 'completed'
                && $item->completed_at
                && Carbon::parse($item->completed_at)->setTimezone('Asia/Jakarta')->toDateString() === $dateStr;
        })->sortBy([
            ['completed_at', 'asc'],
            ['id', 'asc'],
        ])->values();

        $blockedItems = $activeWorkItems->filter(function (WorkItem $item) {
            $statusVal = $item->status instanceof WorkItemStatus ? $item->status->value : (string) $item->status;
            return $statusVal === 'blocked';
        })->sortBy([
            ['blocked_at', 'asc'],
            ['id', 'asc'],
        ])->values();

        $dueTodayCount = $activeWorkItems->filter(function (WorkItem $item) use ($dateStr) {
            return $item->planned_end_date && $item->planned_end_date->toDateString() === $dateStr;
        })->count();

        $newTodayCount = $allWorkItems->filter(function (WorkItem $item) use ($dateStr) {
            return $item->created_at && Carbon::parse($item->created_at)->setTimezone('Asia/Jakarta')->toDateString() === $dateStr;
        })->count();

        // 4. Executive Summary
        $summary = [
            'total_active' => $activeWorkItems->count(),
            'completed_today' => $completedTodayItems->count(),
            'new_today' => $newTodayCount,
            'net_delta' => $newTodayCount - $completedTodayItems->count(),
            'due_today' => $dueTodayCount,
            'in_grace' => $graceItems->count(),
            'overdue' => $overdueItems->count(),
            'blocked' => $blockedItems->count(),
            'compliance_percent' => $compliance['percent'],
        ];

        // 5. Personnel Activity & Daily Results (Grouped by Area)
        $personnelActivity = $this->buildPersonnelActivity($day, $dateStr);

        // 6. Weekly Plans Progress
        $weeklyPlans = $this->buildWeeklyPlans($day);

        // 7. Schedule Changes Recorded on Snapshot Date
        $scheduleChanges = WorkItemScheduleChange::whereDate('changed_at', $dateStr)
            ->with(['workItem.owner'])
            ->orderBy('changed_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // 8. Issues & Inter-Department Constraints
        $issues = Issue::whereDate('first_reported_at', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->where('status', 'open')
                    ->orWhereDate('first_reported_at', $dateStr);
            })
            ->with(['department', 'area', 'sourceDailyReport'])
            ->orderBy('first_reported_at', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        // 9. Department & Area Workload Summary
        $workloadSummary = $this->buildWorkloadSummary($allWorkItems, $day, $dateStr);

        // 10. Assemble View Payload
        $viewData = [
            'metadata' => $metadata,
            'summary' => $summary,
            'compliance' => $compliance,
            'personnelActivity' => $personnelActivity,
            'overdueItems' => $overdueItems,
            'graceItems' => $graceItems,
            'completedTodayItems' => $completedTodayItems,
            'blockedItems' => $blockedItems,
            'weeklyPlans' => $weeklyPlans,
            'scheduleChanges' => $scheduleChanges,
            'issues' => $issues,
            'workloadSummary' => $workloadSummary,
        ];

        $content = View::make('reports.daily-markdown', $viewData)->render();

        File::put($filePath, $content);

        return [
            'status' => $statusType,
            'date' => $dateStr,
            'mode' => $mode,
            'file_path' => $filePath,
            'message' => "Snapshot {$dateStr}.md successfully generated in {$mode} mode.",
            'content' => $content,
        ];
    }

    private function resolveDate(Carbon|string|null $date): Carbon
    {
        if ($date instanceof Carbon) {
            return $date->copy()->setTimezone('Asia/Jakarta')->startOfDay();
        }

        if (is_string($date) && ! empty($date)) {
            return Carbon::parse($date, 'Asia/Jakarta')->startOfDay();
        }

        return Carbon::now('Asia/Jakarta')->startOfDay();
    }

    private function buildMetadata(Carbon $day, string $mode): array
    {
        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        $dayName = $dayNames[$day->dayOfWeek] ?? $day->format('l');

        return [
            'report_date' => $day->toDateString(),
            'day_name' => $dayName,
            'generated_at' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s') . ' WIB',
            'snapshot_mode' => $mode,
            'timezone' => 'Asia/Jakarta (UTC+07:00)',
            'system_version' => 'Laravel 13.x (Improvement Tracker)',
            'snapshot_id' => 'snap-' . $day->format('Ymd'),
        ];
    }

    private function buildPersonnelActivity(Carbon $day, string $dateStr): Collection
    {
        $areas = Area::with(['department'])
            ->orderBy('name')
            ->get()
            ->sortBy(function (Area $area) {
                return ($area->department?->name ?? 'Z') . ' ' . $area->code;
            })->values();

        $activity = collect();

        foreach ($areas as $area) {
            $reports = DailyReport::where('area_id', $area->id)
                ->whereDate('report_date', $dateStr)
                ->with(['reporter', 'workItems' => function ($q) {
                    $q->orderBy('planned_end_date', 'asc')->orderBy('id', 'asc');
                }])
                ->get();

            if ($reports->isEmpty()) {
                continue;
            }

            foreach ($reports as $report) {
                $reporterName = $report->reporter?->name ?? '—';
                $narrative = trim((string) $report->today_result);

                $associatedItems = $report->workItems->map(function (WorkItem $item) {
                    $statusVal = $item->status instanceof WorkItemStatus ? $item->status->value : (string) $item->status;
                    $typeVal = $item->work_type ? ($item->work_type->value ?? (string) $item->work_type) : 'routine';

                    return (object) [
                        'id' => $item->id,
                        'title' => $item->title,
                        'status' => strtoupper($statusVal),
                        'planned_start_date' => $item->planned_start_date ? $item->planned_start_date->toDateString() : '—',
                        'planned_end_date' => $item->planned_end_date ? $item->planned_end_date->toDateString() : '—',
                        'work_type' => $typeVal,
                        'proof_of_work_url' => $item->proof_of_work_url,
                    ];
                });

                $activity->push((object) [
                    'area_code' => $area->code,
                    'area_name' => $area->name,
                    'department_name' => $area->department?->name ?? '—',
                    'pic_name' => $reporterName,
                    'narrative' => $narrative ?: '(Tidak ada uraian narasi yang disampaikan)',
                    'work_items' => $associatedItems,
                ]);
            }
        }

        return $activity;
    }

    private function buildWeeklyPlans(Carbon $day): Collection
    {
        $weekStart = $day->copy()->startOfWeek(Carbon::MONDAY)->toDateString();

        $plans = WeeklyPlan::whereDate('week_start_date', $weekStart)
            ->with(['user', 'workItems', 'score'])
            ->get()
            ->sortBy(function (WeeklyPlan $plan) {
                return ($plan->user?->name ?? 'Z') . ' ' . $plan->id;
            })->values();

        return $plans->map(function (WeeklyPlan $plan) {
            $totalLinked = $plan->workItems->count();
            $completedLinked = $plan->workItems->filter(function ($item) {
                $statusVal = $item->status instanceof WorkItemStatus ? $item->status->value : (string) $item->status;
                return $statusVal === 'completed';
            })->count();

            $progressPercent = $totalLinked > 0 ? (int) round(($completedLinked / $totalLinked) * 100) : 0;
            $scoreStr = $plan->score ? number_format($plan->score->final_score, 1) : 'Pending';

            return (object) [
                'id' => $plan->id,
                'owner_name' => $plan->user?->name ?? '—',
                'title' => $plan->title,
                'category' => ucfirst((string) $plan->category),
                'impact_level' => ucfirst((string) $plan->impact_level),
                'total_linked' => $totalLinked,
                'completed_linked' => $completedLinked,
                'progress_percent' => $progressPercent,
                'score' => $scoreStr,
            ];
        });
    }

    private function buildWorkloadSummary(Collection $allWorkItems, Carbon $day, string $dateStr): Collection
    {
        $areas = Area::with(['department'])->get();
        $departments = Department::all()->keyBy('id');

        $openStatuses = ['not_started', 'in_progress', 'blocked'];

        $rows = collect();

        foreach ($areas as $area) {
            $areaItems = $allWorkItems->where('area_id', $area->id);

            $activeItems = $areaItems->filter(function (WorkItem $item) use ($openStatuses) {
                $statusVal = $item->status instanceof WorkItemStatus ? $item->status->value : (string) $item->status;
                return in_array($statusVal, $openStatuses);
            });

            $inProgressCount = $activeItems->filter(function (WorkItem $item) {
                $statusVal = $item->status instanceof WorkItemStatus ? $item->status->value : (string) $item->status;
                return $statusVal === 'in_progress';
            })->count();

            $blockedCount = $activeItems->filter(function (WorkItem $item) {
                $statusVal = $item->status instanceof WorkItemStatus ? $item->status->value : (string) $item->status;
                return $statusVal === 'blocked';
            })->count();

            $overdueCount = $activeItems->filter(function (WorkItem $item) use ($day) {
                return WorkingDayService::isOverdueOn($item->planned_end_date, $day);
            })->count();

            $completedTodayCount = $areaItems->filter(function (WorkItem $item) use ($dateStr) {
                $statusVal = $item->status instanceof WorkItemStatus ? $item->status->value : (string) $item->status;
                return $statusVal === 'completed'
                    && $item->completed_at
                    && Carbon::parse($item->completed_at)->setTimezone('Asia/Jakarta')->toDateString() === $dateStr;
            })->count();

            $rows->push((object) [
                'department_name' => $area->department?->name ?? '—',
                'area_code' => $area->code,
                'area_name' => $area->name,
                'active_count' => $activeItems->count(),
                'in_progress_count' => $inProgressCount,
                'blocked_count' => $blockedCount,
                'overdue_count' => $overdueCount,
                'completed_today_count' => $completedTodayCount,
            ]);
        }

        return $rows->sortBy(function ($row) {
            return $row->department_name . ' ' . $row->area_code;
        })->values();
    }
}
