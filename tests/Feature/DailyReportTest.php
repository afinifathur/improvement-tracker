<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\DailyReport;
use App\Models\Issue;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeReport(array $overrides = []): DailyReport
    {
        $reporter = User::factory()->create(['role' => 'spv']);
        $admin = User::factory()->create(['role' => 'admin']);

        $data = array_merge([
            'report_date' => '2026-08-14',
            'reported_by' => $reporter->id,
            'today_result' => 'Completed daily maintenance round.',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ], $overrides);

        return DailyReport::create($data);
    }

    public function test_daily_report_belongs_to_reporter(): void
    {
        $reporter = User::factory()->create(['role' => 'spv']);
        $report = $this->makeReport(['reported_by' => $reporter->id]);

        $this->assertTrue($report->reporter->is($reporter));
    }

    public function test_daily_report_has_work_items(): void
    {
        $report = $this->makeReport();
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range(1, 2) as $i) {
            WorkItem::create([
                'title' => "Work item {$i}",
                'owner_id' => $report->reported_by,
                'original_start_date' => '2026-08-15',
                'original_end_date' => '2026-08-15',
                'planned_start_date' => '2026-08-15',
                'planned_end_date' => '2026-08-15',
                'source_daily_report_id' => $report->id,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
        }

        $this->assertCount(2, $report->workItems);
    }

    public function test_daily_report_has_issues(): void
    {
        $report = $this->makeReport();
        $admin = User::factory()->create(['role' => 'admin']);

        $issue = Issue::create([
            'title' => 'Frequent bearing failure on CNC-05',
            'first_reported_at' => now(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $report->issues()->attach($issue->id, ['note' => 'Observed again today', 'reported_at' => now()]);

        $this->assertCount(1, $report->issues);
        $this->assertTrue($report->issues->contains($issue));
    }

    public function test_daily_report_accepts_area_id(): void
    {
        $area = Area::create(['code' => 'NT-FL', 'name' => 'Netto Flange']);

        $report = $this->makeReport(['area_id' => $area->id]);

        $this->assertTrue($report->area->is($area));
        $this->assertSame($area->id, $report->area_id);
    }

    public function test_two_reports_same_reporter_date_different_areas_coexist(): void
    {
        $reporter = User::factory()->create(['role' => 'spv']);
        $admin = User::factory()->create(['role' => 'admin']);

        $flange = Area::create(['code' => 'NT-FL', 'name' => 'Netto Flange']);
        $fitting = Area::create(['code' => 'NT-PF', 'name' => 'Netto Fitting']);

        DailyReport::create([
            'report_date' => '2026-08-14',
            'reported_by' => $reporter->id,
            'area_id' => $flange->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $second = DailyReport::create([
            'report_date' => '2026-08-14',
            'reported_by' => $reporter->id,
            'area_id' => $fitting->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->assertNotNull($second);
        $this->assertSame(2, DailyReport::where('reported_by', $reporter->id)
            ->whereDate('report_date', '2026-08-14')
            ->count());
    }
}
