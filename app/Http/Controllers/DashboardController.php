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
            $submittedUserIdsForDate = isset($submittedByDate[$dateStr])
                ? array_keys($submittedByDate[$dateStr])
                : [];

            $compliance = $this->calculateCompliance($personnel, $submittedUserIdsForDate);

            $days[] = [
                'dateStr' => $dateStr,
                'weekday' => $cursor->translatedFormat('D'),
                'dayLabel' => $cursor->format('j').' '.$cursor->translatedFormat('M'),
                'submitted' => $compliance['submitted'],
                'total' => $compliance['total'],
                'percent' => $compliance['percent'],
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

        $complianceToday = $this->calculateCompliance($personnel, $submittedTodayIds);
        $missingToday = $complianceToday['missing'];

        // Transform personnel and submittedByDate for compliance matrix rendering (presentation model)
        $pairs = config('reporting.temporary_reporting_pairs', []);

        $dbPersonnelByEmail = [];
        foreach ($personnel as $user) {
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

        foreach ($personnel as $user) {
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

        $personnel = $viewPersonnel->sortBy('name')->values();
        $submittedByDate = $viewSubmittedByDate;

        $prevWeekDate = $weekStart->copy()->subWeek()->toDateString();
        $nextWeekDate = $weekStart->copy()->addWeek()->toDateString();

        return view('dashboard.index', compact(
            'rows', 'kpis', 'maxCount',
            'personnel', 'days', 'submittedByDate', 'missingToday',
            'weekStart', 'weekEnd', 'prevWeekDate', 'nextWeekDate'
        ));
    }

    /**
     * Calculate compliance statistics for a given list of personnel and submitted user IDs.
     * Treats configured reporting pairs as a single reporting obligation.
     */
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
