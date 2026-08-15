<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonViewTest extends TestCase
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

    private function makeWorkItem(array $overrides = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'title' => 'Sample work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-10',
            'original_end_date' => '2026-08-12',
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-12',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_person_route_requires_authentication(): void
    {
        $this->get('/person')->assertRedirect('/login');
    }

    public function test_person_route_accepts_admin_manager_director(): void
    {
        $this->actingAs($this->admin)->get('/person')->assertStatus(200);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'mgr@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);
        $this->actingAs($manager)->get('/person')->assertStatus(200);

        $director = User::create([
            'name' => 'Director User',
            'email' => 'dir@test.com',
            'password' => bcrypt('password'),
            'role' => 'director',
        ]);
        $this->actingAs($director)->get('/person')->assertStatus(200);

        $this->actingAs($this->spvA)->get('/person')->assertStatus(403);
    }

    public function test_person_overview_loads(): void
    {
        $this->makeWorkItem(['title' => 'Alpha task', 'owner_id' => $this->spvA->id]);

        $response = $this->actingAs($this->admin)->get('/person');
        $response->assertStatus(200);

        $people = $response->viewData('people');
        $this->assertNotNull($people);
        $this->assertTrue($people->pluck('user')->pluck('id')->contains($this->spvA->id));
    }

    public function test_selecting_person_filters_work_items(): void
    {
        $this->makeWorkItem(['title' => 'Task A', 'owner_id' => $this->spvA->id]);
        $this->makeWorkItem(['title' => 'Task B', 'owner_id' => $this->spvB->id]);

        $response = $this->actingAs($this->admin)->get("/person?person={$this->spvA->id}");
        $response->assertStatus(200);

        $workItems = $response->viewData('workItems');
        $this->assertCount(1, $workItems);
        $this->assertSame('Task A', $workItems->first()->title);
    }

    public function test_owner_id_filtering_is_correct(): void
    {
        $this->makeWorkItem(['title' => 'Task A', 'owner_id' => $this->spvA->id]);
        $this->makeWorkItem(['title' => 'Task B', 'owner_id' => $this->spvB->id]);

        $response = $this->actingAs($this->admin)->get("/person?person={$this->spvA->id}");

        foreach ($response->viewData('workItems') as $item) {
            $this->assertSame($this->spvA->id, $item->owner_id);
        }
    }

    public function test_status_filtering_works(): void
    {
        $this->makeWorkItem(['title' => 'Blocked task', 'owner_id' => $this->spvA->id, 'status' => 'blocked']);
        $this->makeWorkItem(['title' => 'In progress task', 'owner_id' => $this->spvA->id, 'status' => 'in_progress']);

        $response = $this->actingAs($this->admin)->get("/person?person={$this->spvA->id}&status=blocked");
        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems);
        $this->assertSame('Blocked task', $workItems->first()->title);
    }

    public function test_department_filtering_works(): void
    {
        $this->makeWorkItem(['title' => 'Dept A task', 'owner_id' => $this->spvA->id, 'department_id' => $this->deptA->id]);
        $this->makeWorkItem(['title' => 'Dept B task', 'owner_id' => $this->spvA->id, 'department_id' => $this->deptB->id]);

        $response = $this->actingAs($this->admin)->get("/person?person={$this->spvA->id}&department_id={$this->deptA->id}");
        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems);
        $this->assertSame('Dept A task', $workItems->first()->title);
    }

    public function test_area_filtering_works(): void
    {
        $this->makeWorkItem(['title' => 'Area A task', 'owner_id' => $this->spvA->id, 'area_id' => $this->areaA->id]);
        $this->makeWorkItem(['title' => 'Area B task', 'owner_id' => $this->spvA->id, 'area_id' => $this->areaB->id]);

        $response = $this->actingAs($this->admin)->get("/person?person={$this->spvA->id}&area_id={$this->areaB->id}");
        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems);
        $this->assertSame('Area B task', $workItems->first()->title);
    }

    public function test_search_filtering_works(): void
    {
        $this->makeWorkItem(['title' => 'Alpha task', 'owner_id' => $this->spvA->id]);
        $this->makeWorkItem(['title' => 'Beta task', 'owner_id' => $this->spvA->id]);

        $response = $this->actingAs($this->admin)->get("/person?person={$this->spvA->id}&search=Alpha");
        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems);
        $this->assertSame('Alpha task', $workItems->first()->title);
    }

    public function test_completed_historical_work_items_are_viewable(): void
    {
        $this->makeWorkItem([
            'title' => 'Old completed task',
            'owner_id' => $this->spvA->id,
            'status' => 'completed',
            'planned_start_date' => '2026-01-05',
            'planned_end_date' => '2026-01-08',
        ]);

        $response = $this->actingAs($this->admin)->get("/person?person={$this->spvA->id}&status=completed");
        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems);
        $this->assertSame('Old completed task', $workItems->first()->title);
    }

    public function test_overdue_calculation_works(): void
    {
        $this->makeWorkItem([
            'title' => 'Overdue task',
            'owner_id' => $this->spvA->id,
            'status' => 'in_progress',
            'planned_start_date' => '2026-08-01',
            'planned_end_date' => '2026-08-10',
        ]);

        $response = $this->actingAs($this->admin)->get("/person?person={$this->spvA->id}");
        $summary = $response->viewData('summary');

        $this->assertSame(1, $summary['overdue']);
    }

    public function test_blocked_calculation_works(): void
    {
        $this->makeWorkItem([
            'title' => 'Blocked task',
            'owner_id' => $this->spvA->id,
            'status' => 'blocked',
        ]);

        $response = $this->actingAs($this->admin)->get("/person?person={$this->spvA->id}");
        $summary = $response->viewData('summary');

        $this->assertSame(1, $summary['blocked']);
    }

    public function test_sidebar_route_works(): void
    {
        $response = $this->actingAs($this->admin)->get('/person');
        $this->assertStringContainsString(route('work-items.person'), $response->getContent());
    }
}
