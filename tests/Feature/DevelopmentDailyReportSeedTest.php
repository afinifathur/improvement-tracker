<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevelopmentDailyReportSeedTest extends TestCase
{
    use RefreshDatabase;

    private const DATE = '2026-08-14';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_about_30_daily_reports_exist_for_target_date(): void
    {
        $count = DailyReport::whereDate('report_date', self::DATE)->count();

        $this->assertGreaterThanOrEqual(28, $count);
        $this->assertLessThanOrEqual(35, $count);
    }

    public function test_no_reporter_has_more_than_one_report_for_the_date(): void
    {
        $dupes = DailyReport::select('reported_by')
            ->whereDate('report_date', self::DATE)
            ->groupBy('reported_by')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $this->assertSame(0, $dupes);
    }

    public function test_every_report_has_a_valid_operational_reporter(): void
    {
        $reports = DailyReport::whereDate('report_date', self::DATE)->get();

        $this->assertGreaterThan(0, $reports->count());

        foreach ($reports as $report) {
            $this->assertContains($report->reporter->role, ['spv', 'kabag', 'manager']);
        }
    }

    public function test_every_work_item_belongs_to_a_valid_report(): void
    {
        $items = WorkItem::whereDate('created_at', '>=', self::DATE)->get();

        foreach ($items as $item) {
            $this->assertNotNull($item->source_daily_report_id);
            $this->assertTrue(DailyReport::whereKey($item->source_daily_report_id)->exists());
        }
    }

    public function test_work_item_owner_matches_report_reporter(): void
    {
        $items = WorkItem::with('sourceDailyReport')->get();

        $this->assertGreaterThan(0, $items->count());

        foreach ($items as $item) {
            $this->assertSame($item->sourceDailyReport->reported_by, $item->owner_id);
        }
    }

    public function test_work_item_department_matches_owner(): void
    {
        $items = WorkItem::with('owner')->get();

        foreach ($items as $item) {
            $this->assertSame($item->owner->department_id, $item->department_id);
        }
    }

    public function test_completed_items_have_completed_at(): void
    {
        $items = WorkItem::where('status', 'completed')->get();

        $this->assertGreaterThan(0, $items->count());

        foreach ($items as $item) {
            $this->assertNotNull($item->completed_at);
        }
    }

    public function test_blocked_items_have_blocked_reason_and_blocked_at(): void
    {
        $items = WorkItem::where('status', 'blocked')->get();

        $this->assertGreaterThan(0, $items->count());

        foreach ($items as $item) {
            $this->assertNotNull($item->blocked_reason);
            $this->assertNotNull($item->blocked_at);
        }
    }

    public function test_cancelled_items_have_cancel_reason(): void
    {
        $items = WorkItem::where('status', 'cancelled')->get();

        $this->assertGreaterThan(0, $items->count());

        foreach ($items as $item) {
            $this->assertNotNull($item->cancel_reason);
        }
    }

    public function test_cancelled_items_are_terminal(): void
    {
        $items = WorkItem::where('status', 'cancelled')->get();

        foreach ($items as $item) {
            $this->assertSame('cancelled', $item->status->value);
            $this->assertNull($item->completed_at);
        }
    }

    public function test_work_item_date_ranges_are_valid(): void
    {
        $items = WorkItem::all();

        $this->assertGreaterThan(0, $items->count());

        foreach ($items as $item) {
            $this->assertTrue(
                $item->planned_end_date->gte($item->planned_start_date),
                "Invalid date range for work item {$item->id}"
            );
        }
    }

    public function test_overdue_items_satisfy_overdue_definition(): void
    {
        $overdue = $this->classify(self::DATE)['overdue'];

        $this->assertGreaterThan(0, count($overdue));

        foreach ($overdue as $item) {
            $this->assertLessThan(self::DATE, $item->planned_end_date->toDateString());
            $this->assertNotContains($item->status->value, ['completed', 'cancelled']);
        }
    }

    public function test_current_items_contain_target_date(): void
    {
        $current = $this->classify(self::DATE)['current'];

        $this->assertGreaterThan(0, count($current));

        foreach ($current as $item) {
            $this->assertLessThanOrEqual(self::DATE, $item->planned_start_date->toDateString());
            $this->assertGreaterThanOrEqual(self::DATE, $item->planned_end_date->toDateString());
        }
    }

    public function test_future_items_start_after_target_date(): void
    {
        $future = $this->classify(self::DATE)['future'];

        $this->assertGreaterThan(0, count($future));

        foreach ($future as $item) {
            $this->assertGreaterThan(self::DATE, $item->planned_start_date->toDateString());
        }
    }

    /**
     * @return array{overdue: array, current: array, future: array}
     */
    private function classify(string $date): array
    {
        $items = WorkItem::whereIn('status', ['not_started', 'in_progress', 'blocked'])
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
}
