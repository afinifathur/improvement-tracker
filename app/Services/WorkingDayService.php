<?php

namespace App\Services;

use Carbon\Carbon;

class WorkingDayService
{
    /**
     * Determine if a date is an active working day (Monday - Saturday).
     * Sunday is a non-working day.
     */
    public static function isWorkingDay(Carbon|string $date): bool
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        return ! $carbon->isSunday();
    }

    /**
     * Step forward by N working days strictly following the given date.
     * Example from Friday:
     * addWorkingDays(Friday, 1) -> Saturday (Grace day #1)
     * addWorkingDays(Friday, 2) -> Monday   (Grace day #2)
     * addWorkingDays(Friday, 3) -> Tuesday  (Overdue Day)
     */
    public static function addWorkingDays(Carbon|string $date, int $days): Carbon
    {
        $cursor = ($date instanceof Carbon ? $date->copy() : Carbon::parse($date))->startOfDay();
        $added = 0;
        while ($added < $days) {
            $cursor->addDay();
            if (self::isWorkingDay($cursor)) {
                $added++;
            }
        }
        return $cursor;
    }

    /**
     * Step backward by N working days strictly preceding the given date.
     * Example from Saturday:
     * subWorkingDays(Saturday, 1) -> Friday
     * subWorkingDays(Saturday, 2) -> Thursday
     * subWorkingDays(Saturday, 3) -> Wednesday
     * Example from Tuesday:
     * subWorkingDays(Tuesday, 1) -> Monday
     * subWorkingDays(Tuesday, 2) -> Saturday
     * subWorkingDays(Tuesday, 3) -> Friday
     */
    public static function subWorkingDays(Carbon|string $date, int $days): Carbon
    {
        $cursor = ($date instanceof Carbon ? $date->copy() : Carbon::parse($date))->startOfDay();
        $subtracted = 0;
        while ($subtracted < $days) {
            $cursor->subDay();
            if (self::isWorkingDay($cursor)) {
                $subtracted++;
            }
        }
        return $cursor;
    }

    /**
     * Get the next working day strictly following the given date.
     * Friday -> Saturday
     * Saturday -> Monday
     * Sunday -> Monday
     */
    public static function nextWorkingDay(Carbon|string $date): Carbon
    {
        return self::addWorkingDays($date, 1);
    }

    /**
     * Check if a work item is overdue on a given reference date.
     * Rule: 2 WORKING-DAY GRACE PERIOD.
     * - Day 0: Deadline day -> NOT overdue.
     * - 1st working day after deadline: Grace day #1 -> NOT overdue.
     * - 2nd working day after deadline: Grace day #2 -> NOT overdue.
     * - 3rd working day after deadline: OVERDUE.
     * - Sunday (non-working day): NOT overdue.
     */
    public static function isOverdueOn(Carbon|string|null $plannedEndDate, Carbon|string $referenceDate): bool
    {
        if (! $plannedEndDate) {
            return false;
        }

        $ref = ($referenceDate instanceof Carbon ? $referenceDate->copy() : Carbon::parse($referenceDate))->startOfDay();
        if (! self::isWorkingDay($ref)) {
            return false; // Sunday is a non-working day
        }

        $end = ($plannedEndDate instanceof Carbon ? $plannedEndDate->copy() : Carbon::parse($plannedEndDate))->startOfDay();
        $overdueThresholdDay = self::addWorkingDays($end, 3);

        return $ref->greaterThanOrEqualTo($overdueThresholdDay);
    }

    /**
     * Get the inclusive maximum planned_end_date that is considered overdue on referenceDate.
     * Returns null if referenceDate is a non-working day (nothing escalates into overdue on Sundays).
     */
    public static function overdueThresholdDate(Carbon|string $referenceDate): ?string
    {
        $ref = ($referenceDate instanceof Carbon ? $referenceDate->copy() : Carbon::parse($referenceDate))->startOfDay();
        if (! self::isWorkingDay($ref)) {
            return null; // Sunday is a non-working day
        }

        // On working day R, any deadline P where addWorkingDays(P, 3) <= R is overdue.
        // Therefore, P <= subWorkingDays(R, 3).
        return self::subWorkingDays($ref, 3)->toDateString();
    }
}
