<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkItemViewsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $director;

    private User $spv;

    private Department $deptA;

    private Area $areaA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);

        $this->director = User::create([
            'name' => 'Director User',
            'email' => 'director@test.com',
            'password' => bcrypt('password'),
            'role' => 'director',
        ]);

        $this->spv = User::create([
            'name' => 'SPV User',
            'email' => 'spv@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
        ]);

        $this->deptA = Department::create(['code' => 'D-A', 'name' => 'Dept A']);
        $this->areaA = Area::create(['code' => 'AR-A', 'name' => 'Area A', 'department_id' => $this->deptA->id]);
    }

    public function test_authentication_and_role_access(): void
    {
        $endpoints = ['/plan', '/progress', '/overdue', '/completed'];

        foreach ($endpoints as $url) {
            // Guest redirected
            $response = $this->get($url);
            $response->assertRedirect('/login');

            // Admin ok
            $response = $this->actingAs($this->admin)->get($url);
            $response->assertStatus(200);

            // Manager ok
            $response = $this->actingAs($this->manager)->get($url);
            $response->assertStatus(200);

            // Director ok
            $response = $this->actingAs($this->director)->get($url);
            $response->assertStatus(200);

            // SPV forbidden
            $response = $this->actingAs($this->spv)->get($url);
            $response->assertStatus(403);

            // Logout to reset auth state for next iteration guest check
            auth()->logout();
        }
    }

    public function test_plan_view_lists_all_items_without_default_date_restriction(): void
    {
        $today = now()->toDateString();

        // 1. active item
        $item1 = WorkItem::create([
            'title' => 'Active task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $today,
            'original_end_date' => $today,
            'planned_start_date' => $today,
            'planned_end_date' => $today,
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 2. completed item
        $item2 = WorkItem::create([
            'title' => 'Completed task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $today,
            'original_end_date' => $today,
            'planned_start_date' => $today,
            'planned_end_date' => $today,
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 3. cancelled item
        $item3 = WorkItem::create([
            'title' => 'Cancelled task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $today,
            'original_end_date' => $today,
            'planned_start_date' => $today,
            'planned_end_date' => $today,
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 4. item in future (outside any "today" range)
        $futureDate = now()->addDays(30)->toDateString();
        $item4 = WorkItem::create([
            'title' => 'Future task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $futureDate,
            'original_end_date' => $futureDate,
            'planned_start_date' => $futureDate,
            'planned_end_date' => $futureDate,
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/plan');
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $this->assertNotNull($grouped);

        $flatItems = $grouped->flatten();
        $this->assertCount(4, $flatItems);
        $this->assertTrue($flatItems->contains('id', $item1->id));
        $this->assertTrue($flatItems->contains('id', $item2->id));
        $this->assertTrue($flatItems->contains('id', $item3->id));
        $this->assertTrue($flatItems->contains('id', $item4->id));

        // Test status filtering in Plan View
        $response = $this->actingAs($this->admin)->get('/plan?status=completed');
        $groupedFiltered = $response->viewData('groupedItems');
        $this->assertCount(1, $groupedFiltered->flatten());
        $this->assertSame('Completed task', $groupedFiltered->flatten()->first()->title);

        // Test search filter in Plan View
        $response = $this->actingAs($this->admin)->get('/plan?search=Future');
        $groupedSearch = $response->viewData('groupedItems');
        $this->assertCount(1, $groupedSearch->flatten());
        $this->assertSame('Future task', $groupedSearch->flatten()->first()->title);
    }

    public function test_progress_view_only_shows_in_progress_items(): void
    {
        $today = now()->toDateString();

        // 1. in progress item
        $inProgress = WorkItem::create([
            'title' => 'In progress task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $today,
            'original_end_date' => $today,
            'planned_start_date' => $today,
            'planned_end_date' => $today,
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 2. blocked item
        WorkItem::create([
            'title' => 'Blocked task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $today,
            'original_end_date' => $today,
            'planned_start_date' => $today,
            'planned_end_date' => $today,
            'status' => 'blocked',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 3. completed item
        WorkItem::create([
            'title' => 'Completed task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $today,
            'original_end_date' => $today,
            'planned_start_date' => $today,
            'planned_end_date' => $today,
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/progress');
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $flatItems = $grouped->flatten();

        $this->assertCount(1, $flatItems);
        $this->assertSame('In progress task', $flatItems->first()->title);

        $summary = $response->viewData('summary');
        $this->assertEquals(1, $summary['in_progress']);
        $this->assertEquals(1, $summary['blocked']); // Blocked included in contextual summary
    }

    public function test_overdue_view_rules_and_lateness_calculation(): void
    {
        // 3 working days ago to ensure it is past the 2 working-day grace period
        $overdueDeadline = \App\Services\WorkingDayService::subWorkingDays(now(), 3)->toDateString();
        $today = now()->toDateString();

        // 1. overdue not_started
        $item1 = WorkItem::create([
            'title' => 'Overdue not started',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $overdueDeadline,
            'original_end_date' => $overdueDeadline,
            'planned_start_date' => $overdueDeadline,
            'planned_end_date' => $overdueDeadline,
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 2. overdue in_progress
        $item2 = WorkItem::create([
            'title' => 'Overdue in progress',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $overdueDeadline,
            'original_end_date' => $overdueDeadline,
            'planned_start_date' => $overdueDeadline,
            'planned_end_date' => $overdueDeadline,
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 3. overdue blocked
        $item3 = WorkItem::create([
            'title' => 'Overdue blocked',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $overdueDeadline,
            'original_end_date' => $overdueDeadline,
            'planned_start_date' => $overdueDeadline,
            'planned_end_date' => $overdueDeadline,
            'status' => 'blocked',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 4. overdue completed (should be excluded)
        WorkItem::create([
            'title' => 'Overdue completed',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $overdueDeadline,
            'original_end_date' => $overdueDeadline,
            'planned_start_date' => $overdueDeadline,
            'planned_end_date' => $overdueDeadline,
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 5. non-overdue active (due today)
        WorkItem::create([
            'title' => 'Active due today',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $today,
            'original_end_date' => $today,
            'planned_start_date' => $today,
            'planned_end_date' => $today,
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/overdue');
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $flatItems = $grouped->flatten();

        $this->assertCount(3, $flatItems);
        $this->assertTrue($flatItems->contains('id', $item1->id));
        $this->assertTrue($flatItems->contains('id', $item2->id));
        $this->assertTrue($flatItems->contains('id', $item3->id));

        // Validate "days_overdue" calculation
        $this->assertGreaterThanOrEqual(3, $flatItems->first()->days_overdue);
    }

    public function test_completed_view_only_shows_completed_items(): void
    {
        $today = now()->toDateString();

        // 1. completed item
        $item1 = WorkItem::create([
            'title' => 'Completed task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $today,
            'original_end_date' => $today,
            'planned_start_date' => $today,
            'planned_end_date' => $today,
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 2. cancelled item (should be excluded)
        WorkItem::create([
            'title' => 'Cancelled task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => $today,
            'original_end_date' => $today,
            'planned_start_date' => $today,
            'planned_end_date' => $today,
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/completed');
        $response->assertStatus(200);

        $grouped = $response->viewData('groupedItems');
        $flatItems = $grouped->flatten();

        $this->assertCount(1, $flatItems);
        $this->assertSame('Completed task', $flatItems->first()->title);
    }

    public function test_sidebar_routing_and_navigation_integrity(): void
    {
        $response = $this->actingAs($this->admin)->get('/plan');
        $response->assertStatus(200);

        // Sidebar active link assertions
        $this->assertStringContainsString(route('work-items.plan'), $response->getContent());
        $this->assertStringContainsString(route('work-items.progress'), $response->getContent());
        $this->assertStringContainsString(route('work-items.overdue'), $response->getContent());
        $this->assertStringContainsString(route('work-items.completed'), $response->getContent());

        // Validate Today and This Week views still work
        $responseToday = $this->actingAs($this->admin)->get('/today');
        $responseToday->assertStatus(200);

        $responseWeek = $this->actingAs($this->admin)->get('/this-week');
        $responseWeek->assertStatus(200);
    }
}
