<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\Issue;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_item_belongs_to_area(): void
    {
        $area = Area::create(['code' => 'COR-FL', 'name' => 'Cor Flange']);
        $owner = User::factory()->create(['role' => 'spv']);
        $admin = User::factory()->create(['role' => 'admin']);

        $item = WorkItem::create([
            'title' => 'Follow up hasil produksi heat 304',
            'owner_id' => $owner->id,
            'area_id' => $area->id,
            'original_start_date' => '2026-08-12',
            'original_end_date' => '2026-08-14',
            'planned_start_date' => '2026-08-12',
            'planned_end_date' => '2026-08-14',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->assertTrue($item->area->is($area));
    }

    public function test_issue_belongs_to_area(): void
    {
        $area = Area::create(['code' => 'COR-FL', 'name' => 'Cor Flange']);
        $admin = User::factory()->create(['role' => 'admin']);

        $issue = Issue::create([
            'title' => 'Recurring casting defect',
            'area_id' => $area->id,
            'first_reported_at' => now(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->assertTrue($issue->area->is($area));
    }

    public function test_work_item_snapshot_survives_area_department_change(): void
    {
        $deptA = Department::create(['code' => 'PRD-FL', 'name' => 'Produksi Flange']);
        $deptB = Department::create(['code' => 'PRD-PF', 'name' => 'Produksi Fitting']);
        $area = Area::create(['code' => 'COR-FL', 'name' => 'Cor Flange', 'department_id' => $deptA->id]);
        $owner = User::factory()->create(['role' => 'spv']);
        $admin = User::factory()->create(['role' => 'admin']);

        $item = WorkItem::create([
            'title' => 'Follow up hasil produksi heat 304',
            'owner_id' => $owner->id,
            'area_id' => $area->id,
            'department_id' => $deptA->id,
            'original_start_date' => '2026-08-12',
            'original_end_date' => '2026-08-14',
            'planned_start_date' => '2026-08-12',
            'planned_end_date' => '2026-08-14',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $area->department_id = $deptB->id;
        $area->save();

        $fresh = $item->fresh();

        $this->assertTrue($fresh->department->is($deptA));
        $this->assertTrue($fresh->area->is($area));
    }

    public function test_daily_report_snapshot_survives_area_department_change(): void
    {
        $deptA = Department::create(['code' => 'PRD-FL', 'name' => 'Produksi Flange']);
        $deptB = Department::create(['code' => 'PRD-PF', 'name' => 'Produksi Fitting']);
        $area = Area::create(['code' => 'COR-FL', 'name' => 'Cor Flange', 'department_id' => $deptA->id]);
        $reporter = User::factory()->create(['role' => 'spv']);
        $admin = User::factory()->create(['role' => 'admin']);

        $report = DailyReport::create([
            'report_date' => '2026-08-14',
            'reported_by' => $reporter->id,
            'area_id' => $area->id,
            'department_id' => $deptA->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $area->department_id = $deptB->id;
        $area->save();

        $fresh = $report->fresh();

        $this->assertTrue($fresh->department->is($deptA));
        $this->assertTrue($fresh->area->is($area));
    }

    public function test_legacy_weekly_plan_still_works(): void
    {
        $spv = User::factory()->create(['role' => 'spv']);
        $admin = User::factory()->create(['role' => 'admin']);

        $plan = WeeklyPlan::create([
            'user_id' => $spv->id,
            'title' => 'Legacy plan',
            'expected_output' => 'Expected output',
            'category' => 'improvement',
            'impact_level' => 'medium',
            'week_start_date' => '2026-08-10',
            'week_end_date' => '2026-08-16',
            'status' => 'planned',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->assertTrue($plan->user->is($spv));
    }
}
