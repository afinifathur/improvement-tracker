<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\Department;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use App\Models\PlanScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThisWeekEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $spv;
    private Department $dept;
    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->dept = Department::create(['code' => 'D-PROD', 'name' => 'Production']);

        $this->spv = User::create([
            'name' => 'Supervisor A',
            'email' => 'spva@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->dept->id,
        ]);

        $this->area = Area::create([
            'code' => 'A-PROD-1',
            'name' => 'Assembly Line 1',
            'department_id' => $this->dept->id,
        ]);

        AreaAssignment::create([
            'area_id' => $this->area->id,
            'user_id' => $this->spv->id,
            'role' => 'spv',
            'started_at' => '2026-08-01',
        ]);
    }

    public function test_weekly_plan_and_linked_work_items_appear_on_this_week(): void
    {
        $plan = WeeklyPlan::create([
            'user_id' => $this->spv->id,
            'title' => 'PENINGKATAN EFISIENSI PACKING',
            'expected_output' => 'Rak A-1 sampai A-5 selesai diopname',
            'category' => 'improvement',
            'impact_level' => 'medium',
            'week_start_date' => '2026-08-17',
            'week_end_date' => '2026-08-23',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $item = WorkItem::create([
            'title' => 'Opname Rak B-2',
            'owner_id' => $this->spv->id,
            'weekly_plan_id' => $plan->id,
            'original_start_date' => '2026-08-17',
            'original_end_date' => '2026-08-17',
            'planned_start_date' => '2026-08-17',
            'planned_end_date' => '2026-08-17',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get('/this-week?date=2026-08-17');
        $response->assertStatus(200);

        $response->assertSee('Supervisor A');
        $response->assertSee('Production');
        $response->assertSee('PENINGKATAN EFISIENSI PACKING');
        $response->assertSee('Improvement');
        $response->assertSee('Sedang');
        $response->assertSee('1 / 1 pekerjaan selesai');
        $response->assertSee('Opname Rak B-2');
    }

    public function test_independent_work_items_remain_visible_under_independent_section(): void
    {
        $item = WorkItem::create([
            'title' => 'Kirim barang PT A',
            'owner_id' => $this->spv->id,
            'weekly_plan_id' => null,
            'original_start_date' => '2026-08-17',
            'original_end_date' => '2026-08-17',
            'planned_start_date' => '2026-08-17',
            'planned_end_date' => '2026-08-17',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->admin)->get('/this-week?date=2026-08-17');
        $response->assertStatus(200);

        // Section header
        $response->assertSee('PEKERJAAN DI LUAR RENCANA MINGGUAN');
        $response->assertSee('Kirim barang PT A');
    }

    public function test_weekly_plan_can_be_evaluated_inline_completed_with_optional_notes_proofs(): void
    {
        $plan = WeeklyPlan::create([
            'user_id' => $this->spv->id,
            'title' => 'PLAN COMPLETED TEST',
            'expected_output' => 'Expected Output',
            'category' => 'improvement',
            'impact_level' => 'low',
            'week_start_date' => '2026-08-17',
            'week_end_date' => '2026-08-23',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Proofs and notes should be optional for V1 completed
        $response = $this->actingAs($this->admin)->patchJson("/api/weekly-plans/{$plan->id}/status", [
            'status' => 'completed',
            'notes' => 'Evaluation complete notes',
        ]);

        $response->assertOk();
        $this->assertEquals('completed', $plan->fresh()->status);
        $this->assertEquals('Evaluation complete notes', $plan->fresh()->notes);
    }

    public function test_weekly_plan_can_be_evaluated_inline_completed_no_impact(): void
    {
        $plan = WeeklyPlan::create([
            'user_id' => $this->spv->id,
            'title' => 'PLAN COMPLETED NO IMPACT TEST',
            'expected_output' => 'Expected Output',
            'category' => 'improvement',
            'impact_level' => 'low',
            'week_start_date' => '2026-08-17',
            'week_end_date' => '2026-08-23',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->patchJson("/api/weekly-plans/{$plan->id}/status", [
            'status' => 'completed_no_impact',
        ]);

        $response->assertOk();
        $this->assertEquals('completed_no_impact', $plan->fresh()->status);
    }

    public function test_weekly_plan_can_be_evaluated_inline_not_completed(): void
    {
        $plan = WeeklyPlan::create([
            'user_id' => $this->spv->id,
            'title' => 'PLAN FAILED TEST',
            'expected_output' => 'Expected Output',
            'category' => 'improvement',
            'impact_level' => 'low',
            'week_start_date' => '2026-08-17',
            'week_end_date' => '2026-08-23',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->patchJson("/api/weekly-plans/{$plan->id}/status", [
            'status' => 'not_completed',
        ]);

        $response->assertOk();
        $this->assertEquals('not_completed', $plan->fresh()->status);
    }

    public function test_weekly_plan_can_be_evaluated_inline_extended_with_new_end_date_and_reason(): void
    {
        $plan = WeeklyPlan::create([
            'user_id' => $this->spv->id,
            'title' => 'PLAN EXTEND TEST',
            'expected_output' => 'Expected Output',
            'category' => 'improvement',
            'impact_level' => 'low',
            'week_start_date' => '2026-08-17',
            'week_end_date' => '2026-08-23',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Reason/notes is required for extended status
        $response = $this->actingAs($this->admin)->patchJson("/api/weekly-plans/{$plan->id}/status", [
            'status' => 'extended',
        ]);
        $response->assertStatus(422);

        // Success when passing new week_end_date and reason
        $response2 = $this->actingAs($this->admin)->patchJson("/api/weekly-plans/{$plan->id}/status", [
            'status' => 'extended',
            'week_end_date' => '2026-08-30',
            'notes' => 'Extension reason description',
        ]);

        $response2->assertOk();
        $this->assertEquals('extended', $plan->fresh()->status);
        $this->assertEquals('2026-08-30', $plan->fresh()->week_end_date->toDateString());
        $this->assertEquals('Extension reason description', $plan->fresh()->notes);
    }

    public function test_weekly_plan_status_change_does_not_change_work_item_statuses(): void
    {
        $plan = WeeklyPlan::create([
            'user_id' => $this->spv->id,
            'title' => 'STATUS INDEPENDENCE TEST',
            'expected_output' => 'Expected Output',
            'category' => 'improvement',
            'impact_level' => 'low',
            'week_start_date' => '2026-08-17',
            'week_end_date' => '2026-08-23',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $item = WorkItem::create([
            'title' => 'Opname Rak B-2',
            'owner_id' => $this->spv->id,
            'weekly_plan_id' => $plan->id,
            'original_start_date' => '2026-08-17',
            'original_end_date' => '2026-08-17',
            'planned_start_date' => '2026-08-17',
            'planned_end_date' => '2026-08-17',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->admin)->patchJson("/api/weekly-plans/{$plan->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertOk();
        $this->assertEquals('completed', $plan->fresh()->status);
        $this->assertEquals('in_progress', $item->fresh()->status->value);
    }

    public function test_weekly_plan_observer_scoring_still_works(): void
    {
        $plan = WeeklyPlan::create([
            'user_id' => $this->spv->id,
            'title' => 'OBSERVER SCORING TEST',
            'expected_output' => 'Expected Output',
            'category' => 'improvement',
            'impact_level' => 'medium', // Medium has x1.2 multiplier
            'week_start_date' => '2026-08-17',
            'week_end_date' => '2026-08-23',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->patchJson("/api/weekly-plans/{$plan->id}/status", [
            'status' => 'completed', // Completed has base score 100
        ]);

        $response->assertOk();
        $score = PlanScore::where('weekly_plan_id', $plan->id)->first();
        $this->assertNotNull($score);
        $this->assertEquals(120, $score->final_score); // 100 * 1.2 = 120
    }

    public function test_legacy_weekly_plan_closing_still_works(): void
    {
        $plan = WeeklyPlan::create([
            'user_id' => $this->spv->id,
            'title' => 'LEGACY PAGE TEST',
            'expected_output' => 'Expected Output',
            'category' => 'improvement',
            'impact_level' => 'low',
            'week_start_date' => now()->startOfWeek()->toDateString(),
            'week_end_date' => now()->endOfWeek()->toDateString(),
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/weekly-plan/closing');
        $response->assertStatus(200);
        $response->assertSee('LEGACY PAGE TEST');
    }
}
