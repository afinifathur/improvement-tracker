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
        $date = '2026-08-15';

        // Overdue (planned_end_date < D)
        $overdue = WorkItem::create([
            'title' => 'Overdue Work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-10',
            'original_end_date' => '2026-08-14',
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-14',
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
}
