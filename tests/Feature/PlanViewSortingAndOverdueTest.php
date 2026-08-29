<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanViewSortingAndOverdueTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $director;
    protected User $spv;
    protected Department $deptAlu;
    protected Area $areaAlu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptAlu = Department::create(['name' => 'ALUMINIUM', 'code' => 'ALU', 'is_active' => true]);
        $this->areaAlu = Area::create(['name' => 'ALUMINIUM', 'code' => 'ALU-01', 'department_id' => $this->deptAlu->id, 'is_active' => true]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'department_id' => $this->deptAlu->id,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Manager User',
            'email' => 'mgr@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'department_id' => $this->deptAlu->id,
            'is_active' => true,
        ]);

        $this->director = User::create([
            'name' => 'Director User',
            'email' => 'dir@test.com',
            'password' => bcrypt('password'),
            'role' => 'director',
            'department_id' => $this->deptAlu->id,
            'is_active' => true,
        ]);

        $this->spv = User::create([
            'name' => 'ULIL',
            'email' => 'ulil@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptAlu->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Plan view sorting: OVERDUE -> OPEN NORMAL -> COMPLETED, with latest date first in each.
     */
    public function test_plan_view_three_tier_sorting_and_date_desc(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00'); // Saturday 29 Aug 2026

        // Completed items (older and newer)
        $completedOld = WorkItem::create([
            'title' => 'Completed 20 Aug',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-19',
            'original_end_date' => '2026-08-20',
            'planned_start_date' => '2026-08-19',
            'planned_end_date' => '2026-08-20',
            'status' => 'completed',
            'completed_at' => '2026-08-20 15:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $completedNew = WorkItem::create([
            'title' => 'Completed 27 Aug',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-26',
            'original_end_date' => '2026-08-27',
            'planned_start_date' => '2026-08-26',
            'planned_end_date' => '2026-08-27',
            'status' => 'completed',
            'completed_at' => '2026-08-27 15:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Overdue items (deadlines 24 Aug and 26 Aug -> on Sat 29 Aug, threshold is 26 Aug)
        $overdueOld = WorkItem::create([
            'title' => 'Overdue 24 Aug',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-20',
            'original_end_date' => '2026-08-24',
            'planned_start_date' => '2026-08-20',
            'planned_end_date' => '2026-08-24',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $overdueNew = WorkItem::create([
            'title' => 'Overdue 26 Aug',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-24',
            'original_end_date' => '2026-08-26',
            'planned_start_date' => '2026-08-24',
            'planned_end_date' => '2026-08-26',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Normal Open items (deadline 28 Aug [Grace 1] and 29 Aug [Today])
        $normalFri = WorkItem::create([
            'title' => 'Normal 28 Aug (Friday)',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $normalSat = WorkItem::create([
            'title' => 'Normal 29 Aug (Saturday)',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-29',
            'original_end_date' => '2026-08-29',
            'planned_start_date' => '2026-08-29',
            'planned_end_date' => '2026-08-29',
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/plan');
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $items = $grouped->get($this->areaAlu->id);

        $this->assertCount(6, $items);

        // Group 1 (Overdue): 26 Aug before 24 Aug
        $this->assertEquals($overdueNew->id, $items[0]->id); // Overdue 26 Aug
        $this->assertEquals($overdueOld->id, $items[1]->id); // Overdue 24 Aug

        // Group 2 (Normal Open): 29 Aug before 28 Aug
        $this->assertEquals($normalSat->id, $items[2]->id); // Normal 29 Aug
        $this->assertEquals($normalFri->id, $items[3]->id); // Normal 28 Aug

        // Group 3 (Completed): 27 Aug before 20 Aug
        $this->assertEquals($completedNew->id, $items[4]->id); // Completed 27 Aug
        $this->assertEquals($completedOld->id, $items[5]->id); // Completed 20 Aug
    }

    /**
     * Test 2: Concrete ULIL Case on 29 Aug 2026.
     * Deadline 28 Aug (Friday) is NOT overdue on Saturday (Grace 1).
     */
    public function test_ulil_acceptance_case_on_plan_view(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00');

        $friItem = WorkItem::create([
            'title' => 'ULIL Cor 28 Aug',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/plan');
        $response->assertStatus(200);

        $summary = $response->viewData('summary');
        $this->assertEquals(0, $summary['overdue']);
        $this->assertEquals(1, $summary['active']);

        $items = $response->viewData('groupedItems')->get($this->areaAlu->id);
        $this->assertSame('current', $items->first()->classification);
    }

    /**
     * Test 3: Plan view search and filters.
     */
    public function test_plan_view_filters_and_authorization(): void
    {
        $item = WorkItem::create([
            'title' => 'Special Filter Task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-29',
            'original_end_date' => '2026-08-29',
            'planned_start_date' => '2026-08-29',
            'planned_end_date' => '2026-08-29',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Search
        $res = $this->actingAs($this->admin)->get('/plan?search=Special');
        $res->assertStatus(200);
        $this->assertCount(1, $res->viewData('groupedItems')->get($this->areaAlu->id));

        // Status filter
        $res = $this->actingAs($this->manager)->get('/plan?status=completed');
        $res->assertStatus(200);
        $this->assertFalse($res->viewData('groupedItems')->has($this->areaAlu->id));

        // Director access
        $res = $this->actingAs($this->director)->get('/plan');
        $res->assertStatus(200);

        // SPV forbidden
        $res = $this->actingAs($this->spv)->get('/plan');
        $res->assertStatus(403);
    }
}
