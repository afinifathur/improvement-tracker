<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThisWeekViewTest extends TestCase
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

    public function test_this_week_route_requires_authentication(): void
    {
        $response = $this->get('/this-week');
        $response->assertRedirect('/login');
    }

    public function test_this_week_route_accepts_admin_manager_director(): void
    {
        $response = $this->actingAs($this->admin)->get('/this-week');
        $response->assertStatus(200);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'mgr@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);
        $response = $this->actingAs($manager)->get('/this-week');
        $response->assertStatus(200);

        $director = User::create([
            'name' => 'Director User',
            'email' => 'dir@test.com',
            'password' => bcrypt('password'),
            'role' => 'director',
        ]);
        $response = $this->actingAs($director)->get('/this-week');
        $response->assertStatus(200);

        // SPV should be unauthorized (no web UI access)
        $response = $this->actingAs($this->spvA)->get('/this-week');
        $response->assertStatus(403);
    }

    public function test_monday_to_sunday_boundaries_calculated_correctly(): void
    {
        // 2026-08-15 is Saturday. Monday is 10th, Sunday is 16th.
        $response = $this->actingAs($this->admin)->get('/this-week?date=2026-08-15');
        $response->assertStatus(200);
        $response->assertViewHas('weekStart', '2026-08-10');
        $response->assertViewHas('weekEnd', '2026-08-16');
        $response->assertViewHas('weekNumber', 33);
    }

    public function test_date_navigation_works(): void
    {
        // Test date selector param directly
        $response = $this->actingAs($this->admin)->get('/this-week?date=2026-08-20');
        $response->assertStatus(200);
        $response->assertViewHas('weekStart', '2026-08-17');
        $response->assertViewHas('weekEnd', '2026-08-23');
        $response->assertViewHas('weekNumber', 34);
    }

    public function test_work_items_intersecting_the_week_are_returned(): void
    {
        // Selected week: 2026-08-10 to 2026-08-16 (D = 2026-08-15)

        // Case 1: Starts before, ends during
        $item1 = WorkItem::create([
            'title' => 'Starts before ends during',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-08',
            'original_end_date' => '2026-08-12',
            'planned_start_date' => '2026-08-08',
            'planned_end_date' => '2026-08-12',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Case 2: Starts during, ends after
        $item2 = WorkItem::create([
            'title' => 'Starts during ends after',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-14',
            'original_end_date' => '2026-08-20',
            'planned_start_date' => '2026-08-14',
            'planned_end_date' => '2026-08-20',
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Case 3: Completely inside
        $item3 = WorkItem::create([
            'title' => 'Completely inside',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-11',
            'original_end_date' => '2026-08-15',
            'planned_start_date' => '2026-08-11',
            'planned_end_date' => '2026-08-15',
            'status' => 'blocked',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Case 4: Starts before, ends after
        $item4 = WorkItem::create([
            'title' => 'Starts before ends after',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-05',
            'original_end_date' => '2026-08-25',
            'planned_start_date' => '2026-08-05',
            'planned_end_date' => '2026-08-25',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/this-week?date=2026-08-15');
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $items = $grouped->get($this->areaA->id);

        $this->assertCount(4, $items);
        $this->assertTrue($items->contains($item1));
        $this->assertTrue($items->contains($item2));
        $this->assertTrue($items->contains($item3));
        $this->assertTrue($items->contains($item4));
    }

    public function test_work_items_outside_the_week_are_excluded(): void
    {
        // Selected week: 2026-08-10 to 2026-08-16

        // Case 1: Completely before
        WorkItem::create([
            'title' => 'Before week',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-01',
            'original_end_date' => '2026-08-09',
            'planned_start_date' => '2026-08-01',
            'planned_end_date' => '2026-08-09',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Case 2: Completely after
        WorkItem::create([
            'title' => 'After week',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-17',
            'original_end_date' => '2026-08-20',
            'planned_start_date' => '2026-08-17',
            'planned_end_date' => '2026-08-20',
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/this-week?date=2026-08-15');
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $this->assertEmpty($grouped); // No groups because no items intersect
    }

    public function test_completed_and_cancelled_items_are_excluded_by_default(): void
    {
        // Intersecting Completed Item
        $completed = WorkItem::create([
            'title' => 'Completed Work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-12',
            'original_end_date' => '2026-08-15',
            'planned_start_date' => '2026-08-12',
            'planned_end_date' => '2026-08-15',
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Intersecting Cancelled Item
        $cancelled = WorkItem::create([
            'title' => 'Cancelled Work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-12',
            'original_end_date' => '2026-08-15',
            'planned_start_date' => '2026-08-12',
            'planned_end_date' => '2026-08-15',
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/this-week?date=2026-08-15');
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $this->assertEmpty($grouped);
    }

    public function test_explicit_completed_status_filter_returns_completed_items(): void
    {
        $completed = WorkItem::create([
            'title' => 'Completed Work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-12',
            'original_end_date' => '2026-08-15',
            'planned_start_date' => '2026-08-12',
            'planned_end_date' => '2026-08-15',
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/this-week?date=2026-08-15&status=completed');
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $this->assertNotNull($grouped);
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Completed Work', $grouped->flatten()->first()->title);
    }

    public function test_explicit_cancelled_status_filter_returns_cancelled_items(): void
    {
        $cancelled = WorkItem::create([
            'title' => 'Cancelled Work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-12',
            'original_end_date' => '2026-08-15',
            'planned_start_date' => '2026-08-12',
            'planned_end_date' => '2026-08-15',
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/this-week?date=2026-08-15&status=cancelled');
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $this->assertNotNull($grouped);
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Cancelled Work', $grouped->flatten()->first()->title);
    }

    public function test_filters_work_correctly(): void
    {
        $date = '2026-08-15';

        // Item A: Dept A, Area A, Spv A
        $itemA = WorkItem::create([
            'title' => 'Item A task description',
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
        $itemB = WorkItem::create([
            'title' => 'Item B task description',
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
        $response = $this->actingAs($this->admin)->get("/this-week?date={$date}&department_id={$this->deptA->id}");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Item A task description', $grouped->flatten()->first()->title);

        // Test Area Filter
        $response = $this->actingAs($this->admin)->get("/this-week?date={$date}&area_id={$this->areaB->id}");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Item B task description', $grouped->flatten()->first()->title);

        // Test Person Filter
        $response = $this->actingAs($this->admin)->get("/this-week?date={$date}&owner_id={$this->spvA->id}");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Item A task description', $grouped->flatten()->first()->title);

        // Test Status Filter
        $response = $this->actingAs($this->admin)->get("/this-week?date={$date}&status=blocked");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Item B task description', $grouped->flatten()->first()->title);

        // Test Search Filter
        $response = $this->actingAs($this->admin)->get("/this-week?date={$date}&search=description");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(2, $grouped->flatten());

        $response = $this->actingAs($this->admin)->get("/this-week?date={$date}&search=Item%20B");
        $grouped = $response->viewData('groupedItems');
        $this->assertCount(1, $grouped->flatten());
        $this->assertSame('Item B task description', $grouped->flatten()->first()->title);
    }

    public function test_area_grouping_and_null_area_visibility(): void
    {
        $date = '2026-08-15';

        // Assigned to Area A
        WorkItem::create([
            'title' => 'Assigned',
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

        // Unassigned area
        WorkItem::create([
            'title' => 'Unassigned',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => null,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/this-week?date={$date}");
        $grouped = $response->viewData('groupedItems');

        $this->assertTrue($grouped->has($this->areaA->id));
        $this->assertTrue($grouped->has(null) || $grouped->has(''));

        $this->assertCount(1, $grouped->get($this->areaA->id));
        $unassigned = $grouped->get(null) ?? $grouped->get('');
        $this->assertCount(1, $unassigned);
        $this->assertSame('Unassigned', $unassigned->first()->title);
    }

    public function test_sidebar_routes_resolve_correctly(): void
    {
        $response = $this->actingAs($this->admin)->get('/this-week');
        $response->assertStatus(200);

        // Intended operational routes
        $this->assertStringContainsString(route('work-items.today'), $response->getContent());
        $this->assertStringContainsString(route('work-items.this-week'), $response->getContent());
        $this->assertStringContainsString(route('daily-reports.index'), $response->getContent());
        $this->assertStringContainsString(route('dashboard'), $response->getContent());
        $this->assertStringContainsString(route('weekly-plans.closing'), $response->getContent());
        $this->assertStringContainsString(route('rankings'), $response->getContent());
    }
}
