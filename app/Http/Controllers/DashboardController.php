<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Management dashboard: a read-only projection over the WorkItem dataset,
     * plus a daily plan submission compliance matrix derived from DailyReport.
     */
    public function index(Request $request): View
    {
        // Operational personnel are users holding an organizational assignment.
        // This excludes system/authentication accounts that exist only in the
        // users table (admin, director, management representative, etc.).
        $personnel = User::whereHas('areaAssignments')->orderBy('name')->get();
        $ownerIds = $personnel->pluck('id');

        // Aggregate unfinished workload per owner in a single SQL query.
        $unfinishedCounts = WorkItem::query()
            ->whereIn('owner_id', $ownerIds)
            ->whereIn('status', ['not_started', 'in_progress', 'blocked'])
            ->selectRaw('owner_id, COUNT(*) as total')
            ->groupBy('owner_id')
            ->pluck('total', 'owner_id');

        $rows = $personnel
            ->map(fn (User $user) => [
                'name' => $user->name,
                'count' => (int) $unfinishedCounts->get($user->id, 0),
            ])
            ->sortByDesc('count')
            ->values();

        $today = now();
        $todayStr = $today->toDateString();

        $kpis = [
            'total' => WorkItem::whereIn('owner_id', $ownerIds)->count(),
            'unfinished' => WorkItem::whereIn('owner_id', $ownerIds)
                ->whereIn('status', ['not_started', 'in_progress', 'blocked'])
                ->count(),
            'overdue' => WorkItem::whereIn('owner_id', $ownerIds)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereDate('planned_end_date', '<', $todayStr)
                ->count(),
            'completed' => WorkItem::whereIn('owner_id', $ownerIds)
                ->where('status', 'completed')
                ->count(),
        ];

        $maxCount = max(1, (int) $rows->max('count'));

        // ---- Daily plan compliance (KEPATUHAN RENCANA HARIAN) ----
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

        $submissions = DailyReport::query()
            ->whereIn('reported_by', $ownerIds)
            ->whereBetween('report_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get(['reported_by', 'report_date']);

        $submittedByDate = [];
        foreach ($submissions as $report) {
            $submittedByDate[$report->report_date->toDateString()][$report->reported_by] = true;
        }

        $days = [];
        $cursor = $weekStart->copy();
        while ($cursor->lte($weekEnd)) {
            $dateStr = $cursor->toDateString();
            $submitted = count($submittedByDate[$dateStr] ?? []);
            $days[] = [
                'dateStr' => $dateStr,
                'weekday' => $cursor->translatedFormat('D'),
                'dayLabel' => $cursor->format('j').' '.$cursor->translatedFormat('M'),
                'submitted' => $submitted,
                'total' => $personnel->count(),
                'percent' => $personnel->count() > 0 ? (int) round($submitted / $personnel->count() * 100) : 0,
                'isToday' => $dateStr === $todayStr,
            ];
            $cursor->addDay();
        }

        // Personnel who have not yet submitted a daily report for today.
        $submittedTodayIds = DailyReport::query()
            ->whereIn('reported_by', $ownerIds)
            ->whereDate('report_date', $todayStr)
            ->pluck('reported_by')
            ->unique()
            ->all();

        $missingToday = $personnel
            ->filter(fn (User $user) => ! in_array($user->id, $submittedTodayIds, true))
            ->pluck('name');

        $prevWeekDate = $weekStart->copy()->subWeek()->toDateString();
        $nextWeekDate = $weekStart->copy()->addWeek()->toDateString();

        return view('dashboard.index', compact(
            'rows', 'kpis', 'maxCount',
            'personnel', 'days', 'submittedByDate', 'missingToday',
            'weekStart', 'weekEnd', 'prevWeekDate', 'nextWeekDate'
        ));
    }
}
