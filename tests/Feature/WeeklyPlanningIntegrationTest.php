<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\Department;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyPlanningIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function reporter(string $role = 'spv'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function area(): Area
    {
        return Area::create([
            'code' => 'A-'.uniqid(),
            'name' => 'Test Area',
        ]);
    }

    public function test_relationships_and_scope(): void
    {
        $admin = $this->admin();
        $spv = $this->reporter();
        $area = $this->area();

        // Create weekly plan
        $plan = WeeklyPlan::create([
            'user_id' => $spv->id,
            'title' => 'Sample Weekly Plan',
            'expected_output' => 'Expected Output',
            'category' => 'improvement',
            'impact_level' => 'low',
            'week_start_date' => '2026-08-10',
            'week_end_date' => '2026-08-16',
            'status' => 'planned',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        // Create work item linked to weekly plan
        $item = WorkItem::create([
            'title' => 'Test Work Item',
            'owner_id' => $spv->id,
            'weekly_plan_id' => $plan->id,
            'original_start_date' => '2026-08-10',
            'original_end_date' => '2026-08-11',
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-11',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->assertEquals($plan->id, $item->weeklyPlan->id);
        $this->assertCount(1, $plan->workItems);
        $this->assertEquals($item->id, $plan->workItems->first()->id);

        // Test AreaAssignment scopeActiveOn and activeOn method
        $assignment = AreaAssignment::create([
            'area_id' => $area->id,
            'user_id' => $spv->id,
            'role' => 'spv',
            'started_at' => '2026-08-10',
            'ended_at' => '2026-08-12',
        ]);

        $this->assertTrue($assignment->activeOn(Carbon::parse('2026-08-11')));
        $this->assertFalse($assignment->activeOn(Carbon::parse('2026-08-15')));
        $this->assertTrue(AreaAssignment::query()->activeOn('2026-08-11')->where('id', $assignment->id)->exists());
        $this->assertFalse(AreaAssignment::query()->activeOn('2026-08-15')->where('id', $assignment->id)->exists());
    }

    public function test_validation_rules_on_store_and_update(): void
    {
        $admin = $this->admin();
        $spv1 = $this->reporter();
        $spv2 = $this->reporter();
        $area = $this->area();

        // Create weekly plan for spv1
        $plan = WeeklyPlan::create([
            'user_id' => $spv1->id,
            'title' => 'SPV1 Plan',
            'expected_output' => 'Output',
            'category' => 'improvement',
            'impact_level' => 'low',
            'week_start_date' => '2026-08-10',
            'week_end_date' => '2026-08-16',
            'status' => 'planned',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        // Store daily report: validation should fail if weekly_plan_id belongs to spv2
        $response = $this->actingAs($admin)->post(route('daily-reports.store'), [
            'reported_by' => $spv2->id,
            'area_id' => $area->id,
            'report_date' => '2026-08-11',
            'work_items' => [
                [
                    'title' => 'Task',
                    'weekly_plan_id' => $plan->id, // belongs to spv1
                    'planned_start_date' => '2026-08-11',
                    'planned_end_date' => '2026-08-12',
                ],
            ],
        ]);
        $response->assertSessionHasErrors();

        // Store daily report: should succeed if weekly_plan_id belongs to spv1
        $response2 = $this->actingAs($admin)->post(route('daily-reports.store'), [
            'reported_by' => $spv1->id,
            'area_id' => $area->id,
            'report_date' => '2026-08-11',
            'work_items' => [
                [
                    'title' => 'Task',
                    'weekly_plan_id' => $plan->id, // belongs to spv1
                    'planned_start_date' => '2026-08-11',
                    'planned_end_date' => '2026-08-12',
                ],
            ],
        ]);
        $response2->assertSessionHasNoErrors();
        $this->assertDatabaseHas('work_items', [
            'weekly_plan_id' => $plan->id,
            'title' => 'Task',
        ]);
    }

    public function test_work_item_status_update_ajax(): void
    {
        $admin = $this->admin();
        $spv = $this->reporter();

        $item = WorkItem::create([
            'title' => 'Task to Update',
            'owner_id' => $spv->id,
            'original_start_date' => '2026-08-10',
            'original_end_date' => '2026-08-11',
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-11',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        // Patch to completed
        $response = $this->actingAs($admin)->patch(route('work-items.update-status', $item), [
            'status' => 'completed',
        ]);
        $response->assertOk();
        $this->assertEquals('completed', $item->fresh()->status->value);
        $this->assertNotNull($item->fresh()->completed_at);

        // Patch to blocked
        $dept = Department::create(['code' => 'TEST', 'name' => 'Test Department']);
        $response2 = $this->actingAs($admin)->patch(route('work-items.update-status', $item), [
            'status' => 'blocked',
            'blocked_reason' => 'waiting_sparepart',
            'blocked_reason_note' => 'Needs part X',
            'blocked_by_department_id' => $dept->id,
        ]);
        $response2->assertOk();
        $this->assertEquals('blocked', $item->fresh()->status->value);
        $this->assertEquals('waiting_sparepart', $item->fresh()->blocked_reason->value);
        $this->assertEquals('Needs part X', $item->fresh()->blocked_reason_note);
        $this->assertEquals($dept->id, $item->fresh()->blocked_by_department_id);
    }

    public function test_work_item_extension_ajax(): void
    {
        $admin = $this->admin();
        $spv = $this->reporter();

        $item = WorkItem::create([
            'title' => 'Extendable Task',
            'owner_id' => $spv->id,
            'original_start_date' => '2026-08-10',
            'original_end_date' => '2026-08-11',
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-11',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('work-items.extend', $item), [
            'new_end_date' => '2026-08-15',
            'reason' => 'Wait for materials',
        ]);
        $response->assertOk();

        $this->assertEquals('2026-08-15', $item->fresh()->planned_end_date->toDateString());
        $this->assertDatabaseHas('work_item_schedule_changes', [
            'work_item_id' => $item->id,
            'old_end_date' => '2026-08-11 00:00:00',
            'new_end_date' => '2026-08-15 00:00:00',
            'reason' => 'Wait for materials',
        ]);
    }

    public function test_weekly_closing_view_and_warning_card(): void
    {
        $admin = $this->admin();
        $spv = $this->reporter();

        // Create a weekly plan with 0 work items
        $planWithZeroItems = WeeklyPlan::create([
            'user_id' => $spv->id,
            'title' => 'Plan with zero items',
            'expected_output' => 'Output',
            'category' => 'improvement',
            'impact_level' => 'low',
            'week_start_date' => now()->startOfWeek()->toDateString(),
            'week_end_date' => now()->endOfWeek()->toDateString(),
            'status' => 'planned',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('weekly-plans.closing'));
        $response->assertOk();

        // Assert that the plan details are rendered and warning message for zero items is visible
        $response->assertSee('Plan with zero items');
        $response->assertSee('Rencana ini belum memiliki laporan pekerjaan harian.');
    }

    public function test_phase_2_mandatory_scenarios(): void
    {
        $admin = $this->admin();
        $dani = $this->reporter();
        $area = $this->area();

        // Create weekly plan for DANI
        $plan = WeeklyPlan::create([
            'user_id' => $dani->id,
            'title' => 'PENINGKATAN EFISIENSI PACKING',
            'expected_output' => 'Output',
            'category' => 'improvement',
            'impact_level' => 'low',
            'week_start_date' => '2026-08-17',
            'week_end_date' => '2026-08-23',
            'status' => 'planned',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        // Scenario A: Create one independent WorkItem for DANI (weekly_plan_id = null)
        $responseA = $this->actingAs($admin)->post(route('daily-reports.store'), [
            'reported_by' => $dani->id,
            'area_id' => $area->id,
            'report_date' => '2026-08-17',
            'today_result' => 'Laporan A',
            'work_items' => [
                [
                    'title' => 'Independent Task',
                    'weekly_plan_id' => null,
                    'planned_start_date' => '2026-08-17',
                    'planned_end_date' => '2026-08-17',
                ]
            ]
        ]);
        $responseA->assertSessionHasNoErrors();
        $this->assertDatabaseHas('work_items', [
            'title' => 'Independent Task',
            'weekly_plan_id' => null,
        ]);

        // Scenario B: Create one WorkItem for DANI linked to "PENINGKATAN EFISIENSI PACKING"
        $responseB = $this->actingAs($admin)->post(route('daily-reports.store'), [
            'reported_by' => $dani->id,
            'area_id' => $area->id,
            'report_date' => '2026-08-18',
            'today_result' => 'Laporan B',
            'work_items' => [
                [
                    'title' => 'Linked Task B',
                    'weekly_plan_id' => $plan->id,
                    'planned_start_date' => '2026-08-18',
                    'planned_end_date' => '2026-08-18',
                ]
            ]
        ]);
        $responseB->assertSessionHasNoErrors();
        $this->assertDatabaseHas('work_items', [
            'title' => 'Linked Task B',
            'weekly_plan_id' => $plan->id,
        ]);

        // Scenario C: Submit TWO WorkItems in ONE Daily Report:
        // 1. "Opname Rak B-2" -> linked to Weekly Plan
        // 2. "Kirim barang PT A" -> independent
        $responseC = $this->actingAs($admin)->post(route('daily-reports.store'), [
            'reported_by' => $dani->id,
            'area_id' => $area->id,
            'report_date' => '2026-08-19',
            'today_result' => 'Laporan C',
            'work_items' => [
                [
                    'title' => 'Opname Rak B-2',
                    'weekly_plan_id' => $plan->id,
                    'planned_start_date' => '2026-08-19',
                    'planned_end_date' => '2026-08-19',
                ],
                [
                    'title' => 'Kirim barang PT A',
                    'weekly_plan_id' => null,
                    'planned_start_date' => '2026-08-19',
                    'planned_end_date' => '2026-08-19',
                ]
            ]
        ]);
        $responseC->assertSessionHasNoErrors();
        $this->assertDatabaseHas('work_items', [
            'title' => 'Opname Rak B-2',
            'weekly_plan_id' => $plan->id,
        ]);
        $this->assertDatabaseHas('work_items', [
            'title' => 'Kirim barang PT A',
            'weekly_plan_id' => null,
        ]);

        // Scenario D: Verify that /this-week shows correct categorization
        $responseD = $this->actingAs($admin)->get(route('work-items.this-week', ['date' => '2026-08-17']));
        $responseD->assertOk();
        $responseD->assertSee('PENINGKATAN EFISIENSI PACKING');
        $responseD->assertSee('Opname Rak B-2');
        $responseD->assertSee('Kirim barang PT A');
    }
}
