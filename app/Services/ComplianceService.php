<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ComplianceService
{
    /**
     * Evaluate daily report compliance for a specific date.
     *
     * @return array{
     *     date: string,
     *     total: int,
     *     submitted: int,
     *     percent: int,
     *     missing: Collection,
     *     details: Collection
     * }
     */
    public function evaluateDailyCompliance(Carbon|string $date): array
    {
        $day = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();
        $dateStr = $day->toDateString();

        // 1. Resolve eligible personnel active on snapshot date
        $personnel = User::whereHas('areaAssignments', function ($q) use ($day) {
            $q->activeOn($day);
        })->with([
            'department',
            'areaAssignments' => function ($q) use ($day) {
                $q->activeOn($day)->with('area');
            },
        ])->orderBy('name')->get();

        // 2. Fetch actual daily reports submitted for that date
        $reports = DailyReport::whereDate('report_date', $dateStr)
            ->with(['reporter', 'area', 'department'])
            ->get();

        $submittedUserIds = $reports->pluck('reported_by')->unique()->all();

        // Reports keyed by reporter_id for metadata extraction
        $reportsByReporter = $reports->groupBy('reported_by');

        // 3. Calculate aggregate compliance metrics
        $compliance = $this->calculateCompliance($personnel, $submittedUserIds);

        // 4. Build deterministic itemized rows (respecting reporting pairs)
        $pairs = config('reporting.temporary_reporting_pairs', []);
        $personnelByEmail = [];
        foreach ($personnel as $user) {
            $personnelByEmail[strtolower(trim($user->email))] = $user;
        }

        $processedUserIds = [];
        $details = collect();

        // Process Pairs
        foreach ($pairs as $pair) {
            $emailA = strtolower(trim($pair[0] ?? ''));
            $emailB = strtolower(trim($pair[1] ?? ''));

            if (isset($personnelByEmail[$emailA]) && isset($personnelByEmail[$emailB])) {
                $userA = $personnelByEmail[$emailA];
                $userB = $personnelByEmail[$emailB];

                $processedUserIds[$userA->id] = true;
                $processedUserIds[$userB->id] = true;

                $userAReports = $reportsByReporter->get($userA->id) ?? collect();
                $userBReports = $reportsByReporter->get($userB->id) ?? collect();
                $allReports = $userAReports->concat($userBReports);

                $isSubmitted = $allReports->isNotEmpty();
                $firstReport = $allReports->first();

                // Resolve combined area & dept names
                $areasA = $userA->areaAssignments->map(fn ($a) => $a->area?->name)->filter();
                $areasB = $userB->areaAssignments->map(fn ($a) => $a->area?->name)->filter();
                $combinedAreas = $areasA->concat($areasB)->unique()->implode(', ') ?: '—';

                $deptName = $userA->department?->name ?? $userB->department?->name ?? '—';

                $submissionTime = null;
                if ($isSubmitted && $firstReport) {
                    $submissionTime = $firstReport->created_at ? $firstReport->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i') : $dateStr;
                }

                $details->push((object) [
                    'name' => "{$userA->name} / {$userB->name}",
                    'department_name' => $deptName,
                    'area_name' => $combinedAreas,
                    'status' => $isSubmitted ? 'Submitted' : 'Missing',
                    'is_submitted' => $isSubmitted,
                    'submission_time' => $submissionTime ?: '—',
                ]);
            }
        }

        // Process Individual Personnel
        foreach ($personnel as $user) {
            if (isset($processedUserIds[$user->id])) {
                continue;
            }

            $userReports = $reportsByReporter->get($user->id) ?? collect();
            $isSubmitted = $userReports->isNotEmpty();
            $firstReport = $userReports->first();

            $areaNames = $user->areaAssignments->map(fn ($a) => $a->area?->name)->filter()->unique()->implode(', ') ?: '—';
            $deptName = $user->department?->name ?? '—';

            $submissionTime = null;
            if ($isSubmitted && $firstReport) {
                $submissionTime = $firstReport->created_at ? $firstReport->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i') : $dateStr;
            }

            $details->push((object) [
                'name' => $user->name,
                'department_name' => $deptName,
                'area_name' => $areaNames,
                'status' => $isSubmitted ? 'Submitted' : 'Missing',
                'is_submitted' => $isSubmitted,
                'submission_time' => $submissionTime ?: '—',
            ]);
        }

        $sortedDetails = $details->sortBy('name')->values();

        return [
            'date' => $dateStr,
            'total' => $compliance['total'],
            'submitted' => $compliance['submitted'],
            'percent' => $compliance['percent'],
            'missing' => $compliance['missing'],
            'details' => $sortedDetails,
        ];
    }

    /**
     * Authoritative calculation of obligations and submitted count.
     *
     * @param  Collection|array  $personnel
     * @param  array<int>  $submittedUserIds
     * @return array{total: int, submitted: int, percent: int, missing: Collection}
     */
    public function calculateCompliance($personnel, array $submittedUserIds): array
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
