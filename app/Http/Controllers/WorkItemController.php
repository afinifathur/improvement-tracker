<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Department;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use App\Models\WorkItemScheduleChange;
use App\Services\WorkingDayService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WorkItemController extends Controller
{
    public function today(Request $request)
    {
        $date = $this->resolveDate($request->query('date'));

        // Load active departments, areas, and users for filters
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        // Base query with filters
        $baseQuery = WorkItem::query()->with(['owner', 'department', 'area', 'weeklyPlan']);

        if ($search = $request->input('search')) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('department_id')) {
            $baseQuery->where('department_id', $request->input('department_id'));
        }
        if ($request->filled('area_id')) {
            $baseQuery->where('area_id', $request->input('area_id'));
        }
        if ($request->filled('owner_id')) {
            $baseQuery->where('owner_id', $request->input('owner_id'));
        }

        // Compute KPIs on active workload matching other filters (excluding completed/cancelled)
        $kpiItems = (clone $baseQuery)->whereNotIn('status', ['completed', 'cancelled'])->get();

        $classifiedKpiItems = $kpiItems->map(function ($item) use ($date) {
            $start = $item->planned_start_date->toDateString();
            if (WorkingDayService::isOverdueOn($item->planned_end_date, $date)) {
                $item->classification = 'overdue';
            } elseif ($start > $date) {
                $item->classification = 'future';
            } else {
                $item->classification = 'current';
            }

            return $item;
        });

        $summary = [
            'expected' => $classifiedKpiItems->where('classification', 'current')->count(),
            'active' => $classifiedKpiItems->whereIn('classification', ['overdue', 'current', 'future'])->count(),
            'overdue' => $classifiedKpiItems->where('classification', 'overdue')->count(),
            'blocked' => $classifiedKpiItems->where('status.value', 'blocked')->count(),
        ];

        // Apply status filter to final display items
        if ($request->filled('status')) {
            $baseQuery->where('status', $request->input('status'));
        } else {
            // Default active workload only
            $baseQuery->whereNotIn('status', ['completed', 'cancelled']);
        }

        $workItems = $baseQuery->get();

        // Classify display items relative to selected date D
        $classifiedItems = $workItems->map(function ($item) use ($date) {
            $start = $item->planned_start_date->toDateString();

            if (in_array($item->status->value, ['completed', 'cancelled'])) {
                $item->classification = 'historical';
            } elseif (WorkingDayService::isOverdueOn($item->planned_end_date, $date)) {
                $item->classification = 'overdue';
            } elseif ($start > $date) {
                $item->classification = 'future';
            } else {
                $item->classification = 'current';
            }

            return $item;
        });

        // Group by area_id (stable identity)
        $groupedItems = $classifiedItems->groupBy('area_id');

        return view('work-items.today', compact(
            'date',
            'departments',
            'areas',
            'users',
            'summary',
            'groupedItems'
        ));
    }

    public function thisWeek(Request $request)
    {
        $date = $this->resolveDate($request->query('date'));
        $carbonDate = Carbon::parse($date);
        $weekStart = $carbonDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $carbonDate->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $weekNumber = $carbonDate->isoWeek();

        // Load active departments, areas, and users for filters
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        // Base query with filters and week intersection logic
        $baseQuery = WorkItem::query()
            ->with(['owner', 'department', 'area'])
            ->where('planned_start_date', '<=', $weekEnd)
            ->where('planned_end_date', '>=', $weekStart);

        if ($search = $request->input('search')) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('department_id')) {
            $baseQuery->where('department_id', $request->input('department_id'));
        }
        if ($request->filled('area_id')) {
            $baseQuery->where('area_id', $request->input('area_id'));
        }
        if ($request->filled('owner_id')) {
            $baseQuery->where('owner_id', $request->input('owner_id'));
        }

        // Compute KPIs on all intersecting items (excluding cancelled) before status filter is applied
        $kpiItems = (clone $baseQuery)->where('status', '!=', 'cancelled')->get();

        // Classify KPI items
        $classifiedKpiItems = $kpiItems->map(function ($item) use ($date) {
            $start = $item->planned_start_date->toDateString();
            $end = $item->planned_end_date->toDateString();

            if ($item->status->value === 'completed') {
                $item->classification = 'completed';
            } elseif ($item->status->value === 'blocked') {
                $item->classification = 'blocked';
            } elseif ($item->status->value === 'cancelled') {
                $item->classification = 'cancelled';
            } elseif ($end < $date) {
                $item->classification = 'overdue';
            } elseif ($start <= $date && $end >= $date) {
                $item->classification = 'current';
            } else {
                $item->classification = 'upcoming';
            }

            return $item;
        });

        $summary = [
            'planned' => $classifiedKpiItems->where('status.value', 'not_started')->count(),
            'in_progress' => $classifiedKpiItems->where('status.value', 'in_progress')->count(),
            'blocked' => $classifiedKpiItems->where('status.value', 'blocked')->count(),
            'overdue' => $classifiedKpiItems->where('classification', 'overdue')->count(),
            'completed' => $classifiedKpiItems->where('status.value', 'completed')->count(),
        ];

        // Apply status filter to final display items
        if ($request->filled('status')) {
            $baseQuery->where('status', $request->input('status'));
        } else {
            // Default active workload only (exclude completed/cancelled)
            $baseQuery->whereNotIn('status', ['completed', 'cancelled']);
        }

        $workItems = $baseQuery->get();

        // Classify display items relative to selected date D
        $classifiedItems = $workItems->map(function ($item) use ($date) {
            $start = $item->planned_start_date->toDateString();
            $end = $item->planned_end_date->toDateString();

            if ($item->status->value === 'completed') {
                $item->classification = 'completed';
            } elseif ($item->status->value === 'blocked') {
                $item->classification = 'blocked';
            } elseif ($item->status->value === 'cancelled') {
                $item->classification = 'cancelled';
            } elseif ($end < $date) {
                $item->classification = 'overdue';
            } elseif ($start <= $date && $end >= $date) {
                $item->classification = 'current';
            } else {
                $item->classification = 'upcoming';
            }

            return $item;
        });

        // Group by area_id (stable identity)
        $groupedItems = $classifiedItems->groupBy('area_id');

        // Fetch Weekly Plans for this week
        $weeklyPlansQuery = WeeklyPlan::whereDate('week_start_date', $weekStart)->with(['user']);
        if ($request->filled('owner_id')) {
            $weeklyPlansQuery->where('user_id', $request->input('owner_id'));
        }
        if ($request->filled('department_id')) {
            $weeklyPlansQuery->whereHas('user', function ($q) use ($request) {
                $q->where('department_id', $request->input('department_id'));
            });
        }
        $weeklyPlans = $weeklyPlansQuery->get();

        // Group all week items by weekly_plan_id
        $linkedItemsGrouped = WorkItem::whereIn('weekly_plan_id', $weeklyPlans->pluck('id'))
            ->with(['owner', 'department', 'area'])
            ->get()
            ->groupBy('weekly_plan_id');

        // Independent items (weekly_plan_id is null) grouped by area
        $independentGrouped = $classifiedItems->where('weekly_plan_id', null)->groupBy('area_id');

        return view('work-items.this-week', compact(
            'date',
            'weekStart',
            'weekEnd',
            'weekNumber',
            'departments',
            'areas',
            'users',
            'summary',
            'groupedItems',
            'weeklyPlans',
            'linkedItemsGrouped',
            'independentGrouped'
        ));
    }

    public function plan(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $baseQuery = $this->buildBaseQuery($request);

        $date = null;
        if ($request->filled('date')) {
            $date = $this->resolveDate($request->input('date'));
            $baseQuery->where('planned_start_date', '<=', $date)
                ->where('planned_end_date', '>=', $date);
        }

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->input('status'));
        }

        $refDate = $date ?: now()->toDateString();

        $threshold = WorkingDayService::overdueThresholdDate($refDate);
        if ($threshold !== null) {
            $safeThreshold = addslashes($threshold);
            $rankSql = "CASE 
                WHEN status IN ('not_started', 'in_progress', 'blocked') AND date(planned_end_date) <= '{$safeThreshold}' THEN 1
                WHEN status IN ('not_started', 'in_progress', 'blocked') THEN 2
                ELSE 3
            END";
        } else {
            $rankSql = "CASE 
                WHEN status IN ('not_started', 'in_progress', 'blocked') THEN 1
                ELSE 2
            END";
        }

        $workItems = $baseQuery
            ->orderByRaw("{$rankSql} ASC")
            ->orderByDesc('planned_end_date')
            ->orderByDesc('id')
            ->get();

        $kpiQuery = $this->buildBaseQuery($request);
        if ($date) {
            $kpiQuery->where('planned_start_date', '<=', $date)
                ->where('planned_end_date', '>=', $date);
        }
        $kpiItems = $kpiQuery->get();

        $classifiedKpiItems = $kpiItems->map(function ($item) use ($refDate) {
            $start = $item->planned_start_date->toDateString();
            $end = $item->planned_end_date->toDateString();

            if ($item->status->value === 'completed') {
                $item->classification = 'completed';
            } elseif ($item->status->value === 'blocked') {
                $item->classification = 'blocked';
            } elseif ($item->status->value === 'cancelled') {
                $item->classification = 'cancelled';
            } elseif (WorkingDayService::isOverdueOn($item->planned_end_date, $refDate)) {
                $item->classification = 'overdue';
            } elseif ($start > $refDate) {
                $item->classification = 'upcoming';
            } else {
                $item->classification = 'current';
            }

            return $item;
        });

        $summary = [
            'total' => $classifiedKpiItems->count(),
            'active' => $classifiedKpiItems->whereIn('status.value', ['not_started', 'in_progress', 'blocked'])->count(),
            'overdue' => $classifiedKpiItems->where('classification', 'overdue')->count(),
            'completed' => $classifiedKpiItems->where('status.value', 'completed')->count(),
        ];

        $classifiedItems = $workItems->map(function ($item) use ($refDate) {
            $start = $item->planned_start_date->toDateString();
            $end = $item->planned_end_date->toDateString();

            if ($item->status->value === 'completed') {
                $item->classification = 'completed';
            } elseif ($item->status->value === 'blocked') {
                $item->classification = 'blocked';
            } elseif ($item->status->value === 'cancelled') {
                $item->classification = 'cancelled';
            } elseif (WorkingDayService::isOverdueOn($item->planned_end_date, $refDate)) {
                $item->classification = 'overdue';
            } elseif ($start > $refDate) {
                $item->classification = 'upcoming';
            } else {
                $item->classification = 'current';
            }

            return $item;
        });

        $groupedItems = $classifiedItems->groupBy('area_id');

        return view('work-items.plan', compact(
            'date',
            'departments',
            'areas',
            'users',
            'summary',
            'groupedItems'
        ));
    }

    public function progress(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $baseQuery = $this->buildBaseQuery($request)->where('status', 'in_progress');
        $workItems = $baseQuery->get();
        $refDate = now()->toDateString();

        $inProgressCount = $workItems->count();
        $dueTodayCount = $workItems->filter(fn ($item) => $item->planned_end_date->toDateString() === $refDate)->count();
        $overdueCount = $workItems->filter(fn ($item) => WorkingDayService::isOverdueOn($item->planned_end_date, $refDate))->count();
        $blockedCount = $this->buildBaseQuery($request)->where('status', 'blocked')->count();

        $summary = [
            'in_progress' => $inProgressCount,
            'due_today' => $dueTodayCount,
            'overdue' => $overdueCount,
            'blocked' => $blockedCount,
        ];

        $classifiedItems = $workItems->map(function ($item) use ($refDate) {
            $end = $item->planned_end_date->toDateString();
            if (WorkingDayService::isOverdueOn($item->planned_end_date, $refDate)) {
                $item->classification = 'overdue';
            } elseif ($end === $refDate) {
                $item->classification = 'due_today';
            } else {
                $item->classification = 'current';
            }

            return $item;
        });

        $groupedItems = $classifiedItems->groupBy('area_id');

        return view('work-items.progress', compact(
            'departments',
            'areas',
            'users',
            'summary',
            'groupedItems'
        ));
    }

    public function overdue(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $refDate = now()->toDateString();
        $threshold = WorkingDayService::overdueThresholdDate($refDate);

        $baseQuery = $this->buildBaseQuery($request)
            ->whereNotIn('status', ['completed', 'cancelled']);

        if ($threshold !== null) {
            $baseQuery->whereDate('planned_end_date', '<=', $threshold);
        } else {
            $baseQuery->whereRaw('0 = 1');
        }

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->input('status'));
        }

        $workItems = $baseQuery
            ->orderByDesc('planned_end_date')
            ->orderByDesc('id')
            ->get();

        $kpiQuery = $this->buildBaseQuery($request)
            ->whereNotIn('status', ['completed', 'cancelled']);

        if ($threshold !== null) {
            $kpiQuery->whereDate('planned_end_date', '<=', $threshold);
        } else {
            $kpiQuery->whereRaw('0 = 1');
        }

        $kpiItems = $kpiQuery->get();

        $summary = [
            'total' => $kpiItems->count(),
            'not_started' => $kpiItems->where('status.value', 'not_started')->count(),
            'in_progress' => $kpiItems->where('status.value', 'in_progress')->count(),
            'blocked' => $kpiItems->where('status.value', 'blocked')->count(),
        ];

        $classifiedItems = $workItems->map(function ($item) use ($refDate) {
            $end = Carbon::parse($item->planned_end_date);
            $today = Carbon::parse($refDate);
            $item->days_overdue = (int) $end->diffInDays($today);

            return $item;
        });

        $groupedItems = $classifiedItems->groupBy('area_id');

        return view('work-items.overdue', compact(
            'departments',
            'areas',
            'users',
            'summary',
            'groupedItems'
        ));
    }

    public function completed(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $baseQuery = $this->buildBaseQuery($request)->where('status', 'completed');

        $date = null;
        if ($request->filled('date')) {
            $date = $this->resolveDate($request->input('date'));
            $baseQuery->where('planned_end_date', $date);
        }

        $workItems = $baseQuery
            ->orderByRaw('COALESCE(completed_at, planned_end_date) DESC')
            ->orderByDesc('id')
            ->get();

        $summary = [
            'completed' => $workItems->count(),
        ];

        $groupedItems = $workItems->groupBy('area_id');

        return view('work-items.completed', compact(
            'date',
            'departments',
            'areas',
            'users',
            'summary',
            'groupedItems'
        ));
    }

    public function person(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $baseQuery = WorkItem::query()
            ->with(['owner.areaAssignments.area', 'owner.department', 'department', 'area']);

        if ($search = $request->input('search')) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('department_id')) {
            $baseQuery->where('department_id', $request->input('department_id'));
        }
        if ($request->filled('area_id')) {
            $baseQuery->where('area_id', $request->input('area_id'));
        }
        if ($request->filled('from')) {
            $baseQuery->whereDate('planned_start_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $baseQuery->whereDate('planned_end_date', '<=', $request->input('to'));
        }

        if ($request->filled('person')) {
            $baseQuery->where('owner_id', $request->input('person'));

            $selectedPerson = User::with(['areaAssignments.area', 'department'])->find($request->input('person'));
            $summary = $this->workloadSummary((clone $baseQuery)->get());

            $this->applyStatusTab($baseQuery, $request);

            $threshold = WorkingDayService::overdueThresholdDate(now());
            if ($threshold !== null) {
                $safeThreshold = addslashes($threshold);
                $rankSql = "CASE 
                    WHEN status IN ('not_started', 'in_progress', 'blocked') AND date(planned_end_date) <= '{$safeThreshold}' THEN 1
                    WHEN status IN ('not_started', 'in_progress', 'blocked') THEN 2
                    ELSE 3
                END";
            } else {
                $rankSql = "CASE 
                    WHEN status IN ('not_started', 'in_progress', 'blocked') THEN 1
                    ELSE 2
                END";
            }

            $workItems = $baseQuery
                ->orderByRaw("{$rankSql} ASC")
                ->orderByDesc('planned_end_date')
                ->orderByDesc('id')
                ->get();

            return view('work-items.person', [
                'departments' => $departments,
                'areas' => $areas,
                'users' => $users,
                'selectedPerson' => $selectedPerson,
                'summary' => $summary,
                'workItems' => $workItems,
                'people' => collect(),
            ]);
        }

        $people = $this->buildPersonOverview($baseQuery->get());

        return view('work-items.person', [
            'departments' => $departments,
            'areas' => $areas,
            'users' => $users,
            'selectedPerson' => null,
            'summary' => null,
            'workItems' => collect(),
            'people' => $people,
        ]);
    }

    public function area(Request $request)
    {
        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $isUnassigned = $request->filled('area') && $request->input('area') === 'unassigned';
        $selectedArea = ($request->filled('area') && ! $isUnassigned)
            ? Area::with(['department', 'assignments.user'])->find($request->input('area'))
            : null;

        $baseQuery = WorkItem::query()->with(['owner', 'department', 'area']);

        if ($search = $request->input('search')) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('department_id')) {
            $baseQuery->where('department_id', $request->input('department_id'));
        }
        if ($request->filled('owner_id')) {
            $baseQuery->where('owner_id', $request->input('owner_id'));
        }
        if ($request->filled('from')) {
            $baseQuery->whereDate('planned_start_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $baseQuery->whereDate('planned_end_date', '<=', $request->input('to'));
        }

        if ($isUnassigned) {
            $baseQuery->whereNull('area_id');
        } elseif ($selectedArea) {
            $baseQuery->where('area_id', $selectedArea->id);
        }

        $summary = $this->workloadSummary((clone $baseQuery)->get());

        if ($selectedArea || $isUnassigned) {
            $this->applyStatusTab($baseQuery, $request);
            $workItems = $baseQuery->get();

            return view('work-items.area', [
                'departments' => $departments,
                'areas' => $areas,
                'users' => $users,
                'selectedArea' => $selectedArea,
                'isUnassigned' => $isUnassigned,
                'summary' => $summary,
                'workItems' => $workItems,
                'areaRows' => collect(),
            ]);
        }

        $areaRows = $this->buildAreaOverview($baseQuery->get());

        return view('work-items.area', [
            'departments' => $departments,
            'areas' => $areas,
            'users' => $users,
            'selectedArea' => null,
            'isUnassigned' => false,
            'summary' => null,
            'workItems' => collect(),
            'areaRows' => $areaRows,
        ]);
    }

    private function buildPersonOverview($items)
    {
        $grouped = $items->groupBy('owner_id');
        $people = collect();

        foreach ($grouped as $ownerItems) {
            $owner = $ownerItems->first()->owner;
            if (! $owner) {
                continue;
            }

            $people->push((object) [
                'user' => $owner,
                'counts' => $this->workloadCounts($ownerItems),
                'current_areas' => $this->currentAreaNames($owner),
            ]);
        }

        return $people->sortBy(fn ($p) => $p->user->name)->values();
    }

    private function buildAreaOverview($items)
    {
        $countsByArea = $items->groupBy('area_id');
        $allAreas = Area::with(['department', 'assignments.user'])->orderBy('name')->get();

        $rows = collect();
        foreach ($allAreas as $area) {
            $rows->push($this->makeAreaRow($area, $countsByArea->get($area->id) ?? collect()));
        }

        $unassignedItems = $countsByArea->get(null) ?? $countsByArea->get('') ?? collect();
        if ($unassignedItems->isNotEmpty()) {
            $rows->push($this->makeAreaRow(null, $unassignedItems));
        }

        return $rows;
    }

    private function makeAreaRow(?Area $area, $items)
    {
        return (object) [
            'area' => $area,
            'counts' => $this->workloadCounts($items),
            'responsible' => $area ? $this->currentResponsibleNames($area) : '—',
        ];
    }

    private function workloadSummary($items): array
    {
        $counts = $this->workloadCounts($items);

        return [
            'active' => $counts['active'],
            'overdue' => $counts['overdue'],
            'blocked' => $counts['blocked'],
            'completed' => $counts['completed'],
        ];
    }

    private function workloadCounts($items): array
    {
        $now = now();
        $activeStatuses = ['not_started', 'in_progress', 'blocked'];

        return [
            'open' => $items->where('status.value', 'not_started')->count(),
            'in_progress' => $items->where('status.value', 'in_progress')->count(),
            'active' => $items->whereIn('status.value', $activeStatuses)->count(),
            'overdue' => $items->filter(fn ($item) => in_array($item->status->value, $activeStatuses) && WorkingDayService::isOverdueOn($item->planned_end_date, $now))->count(),
            'blocked' => $items->where('status.value', 'blocked')->count(),
            'completed' => $items->where('status.value', 'completed')->count(),
        ];
    }

    private function applyStatusTab($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));

            return;
        }

        match ($request->input('tab', 'all')) {
            'active' => $query->whereIn('status', ['not_started', 'in_progress', 'blocked']),
            'completed' => $query->where('status', 'completed'),
            'blocked' => $query->where('status', 'blocked'),
            default => null,
        };
    }

    private function currentAreaNames(?User $user): string
    {
        if (! $user || ! $user->relationLoaded('areaAssignments')) {
            return '—';
        }

        $names = $user->areaAssignments
            ->filter(fn ($assignment) => $assignment->activeOn(now()))
            ->map(fn ($assignment) => $assignment->area?->name)
            ->filter()
            ->unique()
            ->values();

        return $names->isEmpty() ? '—' : $names->implode(', ');
    }

    private function currentResponsibleNames(?Area $area): string
    {
        if (! $area || ! $area->relationLoaded('assignments')) {
            return '—';
        }

        $names = $area->assignments
            ->filter(fn ($assignment) => $assignment->activeOn(now()))
            ->map(fn ($assignment) => $assignment->user?->name)
            ->filter()
            ->unique()
            ->values();

        return $names->isEmpty() ? '—' : $names->implode(', ');
    }

    public function calendar(Request $request)
    {
        $date = $this->resolveDate($request->query('date'));
        $carbonDate = Carbon::parse($date);
        $monthStart = $carbonDate->copy()->startOfMonth();
        $monthEnd = $carbonDate->copy()->endOfMonth();

        $departments = Department::active()->orderBy('name')->get();
        $areas = Area::active()->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        // All WorkItems intersecting the displayed month.
        $baseQuery = WorkItem::query()
            ->with(['owner', 'department', 'area'])
            ->where('planned_start_date', '<=', $monthEnd->toDateString())
            ->where('planned_end_date', '>=', $monthStart->toDateString());

        if ($search = $request->input('search')) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('department_id')) {
            $baseQuery->where('department_id', $request->input('department_id'));
        }
        if ($request->filled('area_id')) {
            $baseQuery->where('area_id', $request->input('area_id'));
        }
        if ($request->filled('owner_id')) {
            $baseQuery->where('owner_id', $request->input('owner_id'));
        }

        // Metrics describe the month (all statuses, before the status filter).
        $metricItems = (clone $baseQuery)->get();
        $today = now()->toDateString();
        $activeStatuses = ['not_started', 'in_progress', 'blocked'];
        $summary = [
            'work_items' => $metricItems->count(),
            'active' => $metricItems->whereIn('status.value', $activeStatuses)->count(),
            'overdue' => $metricItems->filter(fn ($item) => in_array($item->status->value, $activeStatuses) && $item->planned_end_date->toDateString() < $today)->count(),
            'blocked' => $metricItems->where('status.value', 'blocked')->count(),
            'completed' => $metricItems->where('status.value', 'completed')->count(),
        ];

        if ($request->filled('status')) {
            $baseQuery->where('status', $request->input('status'));
        }

        $workItems = $baseQuery->get();

        // Build Mon-first full-week grid covering the month.
        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        $cursor = $calendarStart->copy();
        while ($cursor->lte($calendarEnd)) {
            $dayDate = $cursor->toDateString();
            $days[] = [
                'date' => $cursor->copy(),
                'inMonth' => $cursor->month === $monthStart->month && $cursor->year === $monthStart->year,
                'items' => $workItems
                    ->filter(fn ($item) => $item->planned_start_date->toDateString() <= $dayDate && $item->planned_end_date->toDateString() >= $dayDate)
                    ->values(),
            ];
            $cursor->addDay();
        }

        return view('work-items.calendar', [
            'date' => $date,
            'monthStart' => $monthStart->toDateString(),
            'monthEnd' => $monthEnd->toDateString(),
            'departments' => $departments,
            'areas' => $areas,
            'users' => $users,
            'summary' => $summary,
            'days' => $days,
        ]);
    }

    private function buildBaseQuery(Request $request)
    {
        $query = WorkItem::query()->with(['owner', 'department', 'area']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }
        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->input('owner_id'));
        }

        return $query;
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

    public function updateStatus(Request $request, WorkItem $item)
    {
        $request->validate([
            'status' => 'required|in:not_started,in_progress,blocked,completed,cancelled',
            'blocked_reason' => 'nullable|string',
            'blocked_reason_note' => 'nullable|string',
            'blocked_by_department_id' => 'nullable|exists:departments,id',
            'cancel_reason' => 'nullable|string',
            'cancel_reason_note' => 'nullable|string',
        ]);

        $status = $request->input('status');
        $updateData = [
            'status' => $status,
            'updated_by' => auth()->id(),
        ];

        if ($status === 'completed') {
            $updateData['completed_at'] = now();
        } else {
            $updateData['completed_at'] = null;
        }

        if ($status === 'blocked') {
            $updateData['blocked_at'] = now();
            $updateData['blocked_reason'] = $request->input('blocked_reason');
            $updateData['blocked_reason_note'] = $request->input('blocked_reason_note');
            $updateData['blocked_by_department_id'] = $request->input('blocked_by_department_id');
        } else {
            $updateData['blocked_at'] = null;
            $updateData['blocked_reason'] = null;
            $updateData['blocked_reason_note'] = null;
            $updateData['blocked_by_department_id'] = null;
        }

        if ($status === 'cancelled') {
            $updateData['cancel_reason'] = $request->input('cancel_reason');
            $updateData['cancel_reason_note'] = $request->input('cancel_reason_note');
        } else {
            $updateData['cancel_reason'] = null;
            $updateData['cancel_reason_note'] = null;
        }

        $item->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diperbarui.',
            'item' => $item->fresh(['owner', 'department', 'area', 'weeklyPlan']),
        ]);
    }

    public function extend(Request $request, WorkItem $item)
    {
        $request->validate([
            'new_end_date' => 'required|date|after_or_equal:planned_start_date',
            'reason' => 'required|string|max:255',
            'reason_note' => 'nullable|string',
        ]);

        $oldEndDate = $item->planned_end_date;
        $newEndDate = Carbon::parse($request->input('new_end_date'));

        WorkItemScheduleChange::create([
            'work_item_id' => $item->id,
            'old_start_date' => $item->planned_start_date,
            'old_end_date' => $oldEndDate,
            'new_start_date' => $item->planned_start_date,
            'new_end_date' => $newEndDate,
            'reason' => $request->input('reason'),
            'reason_note' => $request->input('reason_note'),
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);

        $item->update([
            'planned_end_date' => $newEndDate,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pekerjaan berhasil diperpanjang.',
            'item' => $item->fresh(['owner', 'department', 'area', 'weeklyPlan']),
        ]);
    }
}
