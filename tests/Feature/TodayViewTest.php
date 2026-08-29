<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodayViewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $deptA;

    private Department $deptB;

    private Area $areaA;

    private Area $areaB;

    private User $spvA;

    private User $spvB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->deptA = Department::create(['code' => 'D-A', 'name' => 'Dept A']);
        $this->deptB = Department::create(['code' => 'D-B', 'name' => 'Dept B']);

        $this->areaA = Area::create(['code' => 'AR-A', 'name' => 'Area A', 'department_id' => $this->deptA->id]);
        $this->areaB = Area::create(['code' => 'AR-B', 'name' => 'Area B', 'department_id' => $this->deptB->id]);

        $this->spvA = User::create([
            'name' => 'Supervisor A',
            'email' => 'spva@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptA->id,
        ]);

        $this->spvB = User::create([
            'name' => 'Supervisor B',
            'email' => 'spvb@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptB->id,
        ]);
    }

    public function test_today_route_requires_authentication(): void
    {
        $response = $this->get('/today');
        $response->assertRedirect('/login');
    }

    public function test_today_route_accepts_admin_manager_director(): void
    {
        $response = $this->actingAs($this->admin)->get('/today');
        $response->assertStatus(200);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'mgr@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);
        $response = $this->actingAs($manager)->get('/today');
        $response->assertStatus(200);

        $director = User::create([
            'name' => 'Director User',
            'email' => 'dir@test.com',
            'password' => bcrypt('password'),
            'role' => 'director',
        ]);
        $response = $this->actingAs($director)->get('/today');
        $response->assertStatus(200);

        // SPV should be unauthorized (no web UI access)
        $response = $this->actingAs($this->spvA)->get('/today');
        $response->assertStatus(403);
    }

    public function test_default_selected_date_works(): void
    {
        $response = $this->actingAs($this->admin)->get('/today');
        $response->assertStatus(200);
        $response->assertViewHas('date', today()->toDateString());
    }

    public function test_date_navigation_selection_works(): void
    {
        $targetDate = '2026-08-20';
        $response = $this->actingAs($this->admin)->get("/today?date={$targetDate}");
        $response->assertStatus(200);
        $response->assertViewHas('date', $targetDate);
    }

    public function test_active_work_items_returned_and_completed_cancelled_excluded(): void
    {
        $date = '2026-08-15';

        // Active items
        $activeItem = WorkItem::create([
            'title' => 'Active Work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-15',
            'original_end_date' => '2026-08-15',
            'planned_start_date' => '2026-08-15',
            'planned_end_date' => '2026-08-15',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Completed item
        $completedItem = WorkItem::create([
            'title' => 'Completed Work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-15',
            'original_end_date' => '2026-08-15',
            'planned_start_date' => '2026-08-15',
            'planned_end_date' => '2026-08-15',
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Cancelled item
        $cancelledItem = WorkItem::create([
            'title' => 'Cancelled Work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-15',
            'original_end_date' => '2026-08-15',
            'planned_start_date' => '2026-08-15',
            'planned_end_date' => '2026-08-15',
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/today?date={$date}");
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $this->assertTrue($grouped->has($this->areaA->id));

        $items = $grouped->get($this->areaA->id);
        $this->assertCount(1, $items);
        $this->assertSame('Active Work', $items->first()->title);
    }

    public function test_classification_overdue_current_future(): void
    {
        $date = '2026-08-15'; // Saturday 15 Aug 2026

        // Overdue (planned_end_date 11 Aug -> Tue 11 Aug deadline has working days Wed 12, Thu 13, Fri 14 -> Overdue on Sat 15 Aug)
        $overdue = WorkItem::create([
            'title' => 'Overdue Work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-10',
            'original_end_date' => '2026-08-11',
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-11',
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Current (planned_start_date <= D <= planned_end_date)
        $current = WorkItem::create([
            'title' => 'Current Work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-15',
            'original_end_date' => '2026-08-15',
            'planned_start_date' => '2026-08-15',
            'planned_end_date' => '2026-08-15',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Future (planned_start_date > D)
        $future = WorkItem::create([
            'title' => 'Future Work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-16',
            'original_end_date' => '2026-08-18',
            'planned_start_date' => '2026-08-16',
            'planned_end_date' => '2026-08-18',
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/today?date={$date}");
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $items = $grouped->get($this->areaA->id);

        $this->assertCount(3, $items);
        $this->assertSame('overdue', $items->firstWhere('title', 'Overdue Work')->classification);
        $this->assertSame('current', $items->firstWhere('title', 'Current Work')->classification);
        $this->assertSame('future', $items->firstWhere('title', 'Future Work')->classification);
    }

    public function test_filters_work(): void
    {
        $date = '2026-08-15';

        // Item A: Dept A, Area A, Spv A
        WorkItem::create([
            'title' => 'Item A',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Item B: Dept B, Area B, Spv B
        WorkItem::create([
            'title' => 'Item B',
            'owner_id' => $this->spvB->id,
            'department_id' => $this->deptB->id,
            'area_id' => $this->areaB->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'blocked',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Test Department Filter
        $response = $this->actingAs($this->admin)->get("/today?date={$date}&department_id={$this->deptA->id}");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Item A', $grouped->flatten()->first()->title);

        // Test Area Filter
        $response = $this->actingAs($this->admin)->get("/today?date={$date}&area_id={$this->areaB->id}");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Item B', $grouped->flatten()->first()->title);

        // Test Person Filter
        $response = $this->actingAs($this->admin)->get("/today?date={$date}&owner_id={$this->spvA->id}");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Item A', $grouped->flatten()->first()->title);

        // Test Status Filter
        $response = $this->actingAs($this->admin)->get("/today?date={$date}&status=blocked");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Item B', $grouped->flatten()->first()->title);

        // Test Search Filter
        $response = $this->actingAs($this->admin)->get("/today?date={$date}&search=Item");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(2, $grouped->flatten());

        $response = $this->actingAs($this->admin)->get("/today?date={$date}&search=Item%20B");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Item B', $grouped->flatten()->first()->title);
    }

    public function test_area_grouping(): void
    {
        $date = '2026-08-15';

        WorkItem::create([
            'title' => 'Item 1',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        WorkItem::create([
            'title' => 'Item 2',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/today?date={$date}");
        $grouped = $response->viewData('groupedItems');

        $this->assertTrue($grouped->has($this->areaA->id));
        $this->assertCount(2, $grouped->get($this->areaA->id));
    }

    /**
     * Test Concrete ULIL Acceptance Case: Friday 28 Aug deadline on Saturday 29 Aug is CURRENT (not overdue).
     * On Tuesday 1 Sep it becomes OVERDUE.
     */
    public function test_ulil_concrete_case_friday_deadline_on_saturday_is_current_not_overdue(): void
    {
        $item = WorkItem::create([
            'title' => '3 SHIFT COR DN 80 ISO E01, DN 65 ISO E01, DN 50 ISO E01',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // On Saturday 29 Aug 2026: Reference date Saturday (Grace Day 1) -> NOT overdue, classification = current
        $responseSat = $this->actingAs($this->admin)->get('/today?date=2026-08-29');
        $responseSat->assertStatus(200);

        $summarySat = $responseSat->viewData('summary');
        $this->assertEquals(0, $summarySat['overdue']);
        $this->assertEquals(1, $summarySat['expected']);

        $itemsSat = $responseSat->viewData('groupedItems')->get($this->areaA->id);
        $this->assertSame('current', $itemsSat->first()->classification);

        // On Tuesday 1 Sep 2026: 3rd working day -> OVERDUE
        $responseTue = $this->actingAs($this->admin)->get('/today?date=2026-09-01');
        $responseTue->assertStatus(200);

        $summaryTue = $responseTue->viewData('summary');
        $this->assertEquals(1, $summaryTue['overdue']);

        $itemsTue = $responseTue->viewData('groupedItems')->get($this->areaA->id);
        $this->assertSame('overdue', $itemsTue->first()->classification);
    }

    /**
     * Test Grace Period Cases A through G on Today View.
     */
    public function test_today_grace_period_cases_a_through_g(): void
    {
        // Case A, B, C, D: Friday 28 Aug deadline
        $friItem = WorkItem::create([
            'title' => 'Friday Deadline Item',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Case A: Sat 29 Aug -> NOT overdue
        $res = $this->actingAs($this->admin)->get('/today?date=2026-08-29');
        $this->assertSame('current', $res->viewData('groupedItems')->get($this->areaA->id)->firstWhere('id', $friItem->id)->classification);

        // Case B: Sun 30 Aug -> NOT overdue
        $res = $this->actingAs($this->admin)->get('/today?date=2026-08-30');
        $this->assertSame('current', $res->viewData('groupedItems')->get($this->areaA->id)->firstWhere('id', $friItem->id)->classification);

        // Case C: Mon 31 Aug -> NOT overdue
        $res = $this->actingAs($this->admin)->get('/today?date=2026-08-31');
        $this->assertSame('current', $res->viewData('groupedItems')->get($this->areaA->id)->firstWhere('id', $friItem->id)->classification);

        // Case D: Tue 1 Sep -> OVERDUE
        $res = $this->actingAs($this->admin)->get('/today?date=2026-09-01');
        $this->assertSame('overdue', $res->viewData('groupedItems')->get($this->areaA->id)->firstWhere('id', $friItem->id)->classification);

        // Case E, F, G: Saturday 29 Aug deadline
        $satItem = WorkItem::create([
            'title' => 'Saturday Deadline Item',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-29',
            'original_end_date' => '2026-08-29',
            'planned_start_date' => '2026-08-29',
            'planned_end_date' => '2026-08-29',
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Case E: Mon 31 Aug (Grace 1) -> NOT overdue
        $res = $this->actingAs($this->admin)->get('/today?date=2026-08-31');
        $this->assertSame('current', $res->viewData('groupedItems')->get($this->areaA->id)->firstWhere('id', $satItem->id)->classification);

        // Case F: Tue 1 Sep (Grace 2) -> NOT overdue
        $res = $this->actingAs($this->admin)->get('/today?date=2026-09-01');
        $this->assertSame('current', $res->viewData('groupedItems')->get($this->areaA->id)->firstWhere('id', $satItem->id)->classification);

        // Case G: Wed 2 Sep (Overdue) -> OVERDUE
        $res = $this->actingAs($this->admin)->get('/today?date=2026-09-02');
        $this->assertSame('overdue', $res->viewData('groupedItems')->get($this->areaA->id)->firstWhere('id', $satItem->id)->classification);
    }

    /**
     * Test cross-module consistency between Dashboard and Today view.
     */
    public function test_cross_module_consistency_dashboard_and_today_view(): void
    {
        WorkItem::create([
            'title' => 'Task Friday Deadline',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // On 29 Aug 2026:
        // Dashboard overdueCount = 0
        $dashSat = $this->actingAs($this->admin)->get('/dashboard?date=2026-08-29');
        $dashSat->assertStatus(200);
        $this->assertEquals(0, $dashSat->viewData('overdueCount'));

        // Today overdue count = 0
        $todaySat = $this->actingAs($this->admin)->get('/today?date=2026-08-29');
        $todaySat->assertStatus(200);
        $this->assertEquals(0, $todaySat->viewData('summary')['overdue']);

        // On 1 Sep 2026:
        // Dashboard overdueCount = 1
        $dashTue = $this->actingAs($this->admin)->get('/dashboard?date=2026-09-01');
        $dashTue->assertStatus(200);
        $this->assertEquals(1, $dashTue->viewData('overdueCount'));

        // Today overdue count = 1
        $todayTue = $this->actingAs($this->admin)->get('/today?date=2026-09-01');
        $todayTue->assertStatus(200);
        $this->assertEquals(1, $todayTue->viewData('summary')['overdue']);
    }
}
