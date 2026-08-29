<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use App\Services\WorkingDayService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Management Control & Diagnostic Dashboard
     */
    public function index(Request $request): View
    {
        $today = now();
        $todayStr = $today->toDateString();

        // Optional Reference / Navigation Date (defaults to today)
        $selectedDate = $today->copy();
        if ($request->query('date')) {
            try {
                $selectedDate = Carbon::parse($request->query('date'));
            } catch (\Throwable) {
                $selectedDate = $today->copy();
            }
        }

        $weekStart = $selectedDate->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $selectedDate->copy()->endOfWeek(Carbon::SUNDAY);

        // Filter master options
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();

        $selectedDepartmentId = $request->query('department_id');
        $selectedAreaId = $request->query('area_id');

        // Base WorkItem query with optional filters
        $baseWorkItemQuery = WorkItem::query();
        if ($selectedDepartmentId) {
            $baseWorkItemQuery->where('department_id', $selectedDepartmentId);
        }
        if ($selectedAreaId) {
            $baseWorkItemQuery->where('area_id', $selectedAreaId);
        }

        // Active operational personnel (filter-aware)
        $personnelQuery = User::operationalPersonnel()
            ->with(['department', 'areaAssignments' => function ($q) use ($today) {
                $q->activeOn($today)->with('area');
            }]);

        if ($selectedDepartmentId) {
            $personnelQuery->where('department_id', $selectedDepartmentId);
        }
        if ($selectedAreaId) {
            $personnelQuery->whereHas('areaAssignments', function ($q) use ($selectedAreaId, $today) {
                $q->where('area_id', $selectedAreaId)->activeOn($today);
            });
        }

        $personnel = $personnelQuery->orderBy('name')->get();
        $ownerIds = $personnel->pluck('id');

        // ---- SECTION 1 & 4: MANAGEMENT SUMMARY & WORK FLOW ----
        $openStatuses = ['not_started', 'in_progress', 'blocked'];

        $totalWorkItems = (clone $baseWorkItemQuery)->count();
        $remainingWorkload = (clone $baseWorkItemQuery)->whereIn('status', $openStatuses)->count();

        // Management Overdue Threshold: Monday-Saturday working days, starts next working day
        $selectedThresholdDate = WorkingDayService::overdueThresholdDate($selectedDate);
        if ($selectedThresholdDate !== null) {
            $overdueCount = (clone $baseWorkItemQuery)
                ->whereIn('status', $openStatuses)
                ->whereDate('planned_end_date', '<=', $selectedThresholdDate)
                ->count();
        } else {
            $overdueCount = 0; // Non-working day (Sunday)
        }

        $blockedCount = (clone $baseWorkItemQuery)->where('status', 'blocked')->count();
        $completedTotal = (clone $baseWorkItemQuery)->where('status', 'completed')->count();

        $newToday = (clone $baseWorkItemQuery)->whereDate('created_at', $todayStr)->count();
        $completedToday = (clone $baseWorkItemQuery)->where('status', 'completed')->whereDate('completed_at', $todayStr)->count();
        $netToday = $newToday - $completedToday;

        $newThisWeek = (clone $baseWorkItemQuery)
            ->whereBetween('created_at', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->count();
        $completedThisWeek = (clone $baseWorkItemQuery)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
            ->count();
        $netThisWeek = $newThisWeek - $completedThisWeek;

        // ---- SECTION 2: REMAINING WORKLOAD BREAKDOWN & STACKED BAR DATA PER PERSONEL ----
        // Efficient single aggregate query per owner
        if ($selectedThresholdDate !== null) {
            $safeThreshold = addslashes($selectedThresholdDate);
            $overdueCaseSql = "SUM(CASE WHEN status IN ('not_started', 'in_progress', 'blocked') AND date(planned_end_date) <= '{$safeThreshold}' THEN 1 ELSE 0 END) as overdue_count";
        } else {
            $overdueCaseSql = '0 as overdue_count';
        }

        $rawOwnerStats = (clone $baseWorkItemQuery)
            ->whereIn('owner_id', $ownerIds)
            ->selectRaw("
                owner_id,
                SUM(CASE WHEN status IN ('not_started', 'in_progress', 'blocked') THEN 1 ELSE 0 END) as remaining_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_count,
                {$overdueCaseSql},
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
            ")
            ->groupBy('owner_id')
            ->get()
            ->keyBy('owner_id');

        $personnelWorkloads = $personnel->map(function (User $user) use ($rawOwnerStats) {
            $stats = $rawOwnerStats->get($user->id);
            $activeAreaNames = $user->areaAssignments
                ->map(fn ($a) => $a->area?->name)
                ->filter()
                ->implode(', ');

            $remaining = (int) ($stats->remaining_count ?? 0);
            $overdue = (int) ($stats->overdue_count ?? 0);
            $onTime = max(0, $remaining - $overdue);

            return [
                'user' => $user,
                'area_names' => $activeAreaNames ?: '—',
                'department_name' => $user->department?->name ?? '—',
                'remaining' => $remaining,
                'on_time' => $onTime,
                'overdue' => $overdue,
                'in_progress' => (int) ($stats->in_progress_count ?? 0),
                'blocked' => (int) ($stats->blocked_count ?? 0),
                'completed' => (int) ($stats->completed_count ?? 0),
            ];
        })->sortByDesc('remaining')->values();

        $maxRemaining = max(1, (int) $personnelWorkloads->max('remaining'));

        // ---- SECTION 3: DETERMINISTIC WORKLOAD TREND (7 DAYS of Selected Week) ----
        $trendDays = [];
        $trendCursor = $weekStart->copy();
        while ($trendCursor->lte($weekEnd)) {
            $dayDate = $trendCursor->toDateString();
            $dayEnd = $trendCursor->copy()->endOfDay();

            // Reconstruct historical remaining at the end of dayDate
            $dayRemaining = (clone $baseWorkItemQuery)
                ->where('created_at', '<=', $dayEnd)
                ->where(function ($q) use ($dayEnd) {
                    $q->whereNull('completed_at')->orWhere('completed_at', '>', $dayEnd);
                })
                ->where(function ($q) use ($dayEnd) {
                    $q->where('status', '!=', 'cancelled')->orWhere('updated_at', '>', $dayEnd);
                })
                ->count();

            $dayNew = (clone $baseWorkItemQuery)->whereDate('created_at', $dayDate)->count();
            $dayCompleted = (clone $baseWorkItemQuery)
                ->where('status', 'completed')
                ->whereDate('completed_at', $dayDate)
                ->count();

            $trendDays[] = [
                'dateStr' => $dayDate,
                'dayLabel' => $trendCursor->format('d M'),
                'weekday' => $trendCursor->translatedFormat('D'),
                'remaining' => $dayRemaining,
                'new' => $dayNew,
                'completed' => $dayCompleted,
                'net' => $dayNew - $dayCompleted,
                'isToday' => $dayDate === $todayStr,
                'isFuture' => $trendCursor->greaterThan($today),
            ];
            $trendCursor->addDay();
        }

        $maxTrendRemaining = max(1, collect($trendDays)->where('isFuture', false)->max('remaining') ?? 1);

        // ---- SECTION 5: ATTENTION REQUIRED (Top Overdue & Blocked) ----
        if ($selectedThresholdDate !== null) {
            $attentionOverdue = (clone $baseWorkItemQuery)
                ->whereIn('status', $openStatuses)
                ->whereDate('planned_end_date', '<=', $selectedThresholdDate)
                ->with(['owner', 'department', 'area'])
                ->orderBy('planned_end_date', 'asc')
                ->limit(6)
                ->get()
                ->map(function ($item) use ($today) {
                    $item->days_overdue = $item->planned_end_date
                        ? Carbon::parse($item->planned_end_date)->startOfDay()->diffInDays($today->copy()->startOfDay())
                        : 0;
                    return $item;
                });
        } else {
            $attentionOverdue = collect();
        }

        $attentionBlocked = (clone $baseWorkItemQuery)
            ->where('status', 'blocked')
            ->with(['owner', 'department', 'area', 'blockedByDepartment'])
            ->orderByDesc('blocked_at')
            ->limit(6)
            ->get();

        // ---- SECTION 6: WEEKLY PLANS PROGRESS ----
        $weeklyPlansQuery = WeeklyPlan::whereDate('week_start_date', $weekStart->toDateString())
            ->with(['user', 'workItems']);

        if ($selectedDepartmentId) {
            $weeklyPlansQuery->whereHas('user', fn ($q) => $q->where('department_id', $selectedDepartmentId));
        }

        $weeklyPlans = $weeklyPlansQuery->get()->map(function (WeeklyPlan $plan) {
            $totalLinked = $plan->workItems->count();
            $completedLinked = $plan->workItems->where('status.value', 'completed')->count();
            $progressPercent = $totalLinked > 0 ? (int) round(($completedLinked / $totalLinked) * 100) : 0;

            return (object) [
                'plan' => $plan,
                'total_items' => $totalLinked,
                'completed_items' => $completedLinked,
                'remaining_items' => max(0, $totalLinked - $completedLinked),
                'progress_percent' => $progressPercent,
            ];
        });

        // ---- SECTION 7: DAILY PLAN COMPLIANCE MATRIX ----
        $matrixPersonnel = User::whereHas('areaAssignments', function ($q) use ($weekStart, $weekEnd, $selectedAreaId) {
            $q->where('started_at', '<=', $weekEnd->toDateString())
                ->where(fn ($sq) => $sq->whereNull('ended_at')->orWhere('ended_at', '>=', $weekStart->toDateString()));
            if ($selectedAreaId) {
                $q->where('area_id', $selectedAreaId);
            }
        })->with('areaAssignments')->orderBy('name')->get();

        if ($selectedDepartmentId) {
            $matrixPersonnel = $matrixPersonnel->where('department_id', $selectedDepartmentId)->values();
        }

        $allWeekOwnerIds = $matrixPersonnel->pluck('id');

        $submissions = DailyReport::query()
            ->whereIn('reported_by', $allWeekOwnerIds)
            ->whereBetween('report_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get(['reported_by', 'report_date']);

        $submittedByDate = [];
        foreach ($submissions as $report) {
            $submittedByDate[$report->report_date->toDateString()][$report->reported_by] = true;
        }

        $complianceDays = [];
        $compCursor = $weekStart->copy();
        while ($compCursor->lte($weekEnd)) {
            $dateStr = $compCursor->toDateString();
            $submittedUserIdsForDate = isset($submittedByDate[$dateStr])
                ? array_keys($submittedByDate[$dateStr])
                : [];

            $eligibleForDay = $matrixPersonnel->filter(fn ($u) => $u->areaAssignments->contains(fn ($a) => $a->activeOn($compCursor)));

            $compliance = $this->calculateCompliance($eligibleForDay, $submittedUserIdsForDate);

            $complianceDays[] = [
                'dateStr' => $dateStr,
                'weekday' => $compCursor->translatedFormat('D'),
                'dayLabel' => $compCursor->format('j').' '.$compCursor->translatedFormat('M'),
                'submitted' => $compliance['submitted'],
                'total' => $compliance['total'],
                'percent' => $compliance['percent'],
                'isToday' => $dateStr === $todayStr,
            ];
            $compCursor->addDay();
        }

        // Compliance for today
        $eligibleToday = $matrixPersonnel->filter(fn ($u) => $u->areaAssignments->contains(fn ($a) => $a->activeOn($today)));
        $submittedTodayIds = DailyReport::query()
            ->whereIn('reported_by', $allWeekOwnerIds)
            ->whereDate('report_date', $todayStr)
            ->pluck('reported_by')
            ->unique()
            ->all();

        $complianceToday = $this->calculateCompliance($eligibleToday, $submittedTodayIds);
        $missingToday = $complianceToday['missing'];

        // Compliance presentation transform for pairs
        $pairs = config('reporting.temporary_reporting_pairs', []);
        $dbPersonnelByEmail = [];
        foreach ($matrixPersonnel as $user) {
            $dbPersonnelByEmail[strtolower(trim($user->email))] = $user;
        }

        $processedUserIds = [];
        $viewPersonnel = collect();
        $viewSubmittedByDate = [];

        foreach ($pairs as $pair) {
            $emailA = strtolower(trim($pair[0] ?? ''));
            $emailB = strtolower(trim($pair[1] ?? ''));

            if (isset($dbPersonnelByEmail[$emailA]) && isset($dbPersonnelByEmail[$emailB])) {
                $userA = $dbPersonnelByEmail[$emailA];
                $userB = $dbPersonnelByEmail[$emailB];

                $processedUserIds[$userA->id] = true;
                $processedUserIds[$userB->id] = true;

                $combinedId = "pair:" . min($userA->id, $userB->id) . "_" . max($userA->id, $userB->id);
                $combinedName = "{$userA->name} / {$userB->name}";

                $viewPersonnel->push((object)[
                    'id' => $combinedId,
                    'name' => $combinedName,
                ]);

                foreach ($submittedByDate as $dateStr => $userIds) {
                    $submittedA = isset($userIds[$userA->id]);
                    $submittedB = isset($userIds[$userB->id]);
                    if ($submittedA || $submittedB) {
                        $viewSubmittedByDate[$dateStr][$combinedId] = true;
                    }
                }
            }
        }

        foreach ($matrixPersonnel as $user) {
            if (isset($processedUserIds[$user->id])) {
                continue;
            }

            $viewPersonnel->push((object)[
                'id' => (string) $user->id,
                'name' => $user->name,
            ]);

            foreach ($submittedByDate as $dateStr => $userIds) {
                if (isset($userIds[$user->id])) {
                    $viewSubmittedByDate[$dateStr][(string) $user->id] = true;
                }
            }
        }

        $matrixPersonnel = $viewPersonnel->sortBy('name')->values();
        $submittedByDate = $viewSubmittedByDate;

        $prevWeekDate = $weekStart->copy()->subWeek()->toDateString();
        $nextWeekDate = $weekStart->copy()->addWeek()->toDateString();

        // Backward compatibility variables for existing tests and components
        $days = $complianceDays;
        $personnel = $matrixPersonnel;
        $kpis = [
            'total' => $totalWorkItems,
            'unfinished' => $remainingWorkload,
            'overdue' => $overdueCount,
            'completed' => $completedTotal,
        ];
        $rows = $personnelWorkloads->map(fn ($pw) => [
            'name' => $pw['user']->name,
            'count' => $pw['remaining'],
        ]);
        $maxCount = $maxRemaining;

        return view('dashboard.index', compact(
            'today',
            'selectedDate',
            'weekStart',
            'weekEnd',
            'prevWeekDate',
            'nextWeekDate',
            'departments',
            'areas',
            'selectedDepartmentId',
            'selectedAreaId',
            'remainingWorkload',
            'overdueCount',
            'blockedCount',
            'completedTotal',
            'totalWorkItems',
            'newToday',
            'completedToday',
            'netToday',
            'newThisWeek',
            'completedThisWeek',
            'netThisWeek',
            'personnelWorkloads',
            'maxRemaining',
            'trendDays',
            'maxTrendRemaining',
            'attentionOverdue',
            'attentionBlocked',
            'weeklyPlans',
            'matrixPersonnel',
            'complianceDays',
            'complianceToday',
            'missingToday',
            'submittedByDate',
            'days',
            'personnel',
            'kpis',
            'rows',
            'maxCount'
        ));
    }

    private function calculateCompliance($personnel, array $submittedUserIds): array
    {
        $pairs = config('reporting.temporary_reporting_pairs', []);

        $personnelByEmail = [];
        foreach ($personnel as $user) {
            $personnelByEmail[strtolower(trim($user->email))] = $user;
        }

        $processedUserIds = [];
        $totalObligations = 0;
        $submittedObligations = 0;
        $missing = collect();

        foreach ($pairs as $pair) {
            $emailA = strtolower(trim($pair[0] ?? ''));
            $emailB = strtolower(trim($pair[1] ?? ''));

            if (isset($personnelByEmail[$emailA]) && isset($personnelByEmail[$emailB])) {
                $userA = $personnelByEmail[$emailA];
                $userB = $personnelByEmail[$emailB];

                $processedUserIds[$userA->id] = true;
                $processedUserIds[$userB->id] = true;

                $totalObligations += 1;

                $submittedA = in_array($userA->id, $submittedUserIds);
                $submittedB = in_array($userB->id, $submittedUserIds);

                if ($submittedA || $submittedB) {
                    $submittedObligations += 1;
                } else {
                    $missing->push("{$userA->name} / {$userB->name}");
                }
            }
        }

        foreach ($personnel as $user) {
            if (isset($processedUserIds[$user->id])) {
                continue;
            }

            $totalObligations += 1;

            if (in_array($user->id, $submittedUserIds)) {
                $submittedObligations += 1;
            } else {
                $missing->push($user->name);
            }
        }

        return [
            'total' => $totalObligations,
            'submitted' => $submittedObligations,
            'percent' => $totalObligations > 0 ? (int) round(($submittedObligations / $totalObligations) * 100) : 0,
            'missing' => $missing,
        ];
    }
}
