<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarViewTest extends TestCase
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

    private function flatItems($response)
    {
        return collect($response->viewData('days'))
            ->flatMap(fn ($day) => $day['items'])
            ->unique('id')
            ->values();
    }

    public function test_calendar_route_requires_authentication(): void
    {
        $response = $this->get('/calendar');
        $response->assertRedirect('/login');
    }

    public function test_calendar_route_accepts_admin_manager_director(): void
    {
        $this->actingAs($this->admin)->get('/calendar')->assertStatus(200);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'mgr@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);
        $this->actingAs($manager)->get('/calendar')->assertStatus(200);

        $director = User::create([
            'name' => 'Director User',
            'email' => 'dir@test.com',
            'password' => bcrypt('password'),
            'role' => 'director',
        ]);
        $this->actingAs($director)->get('/calendar')->assertStatus(200);

        $this->actingAs($this->spvA)->get('/calendar')->assertStatus(403);
    }

    public function test_selected_month_is_correct(): void
    {
        $response = $this->actingAs($this->admin)->get('/calendar?date=2026-08-15');

        $response->assertStatus(200);
        $response->assertViewHas('monthStart', '2026-08-01');
        $response->assertViewHas('monthEnd', '2026-08-31');
        $response->assertViewHas('date', '2026-08-15');
    }

    public function test_previous_month_navigation_works(): void
    {
        $response = $this->actingAs($this->admin)->get('/calendar?date=2026-08-15');

        $response->assertStatus(200);
        $this->assertStringContainsString('date=2026-07-01', $response->getContent());
    }

    public function test_next_month_navigation_works(): void
    {
        $response = $this->actingAs($this->admin)->get('/calendar?date=2026-08-15');

        $response->assertStatus(200);
        $this->assertStringContainsString('date=2026-09-01', $response->getContent());
    }

    public function test_work_items_intersecting_month_appear(): void
    {
        $this->makeWorkItem([
            'title' => 'In August',
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-12',
        ]);

        $response = $this->actingAs($this->admin)->get('/calendar?date=2026-08-15');

        $titles = $this->flatItems($response)->pluck('title');
        $this->assertTrue($titles->contains('In August'));
    }

    public function test_work_items_outside_month_do_not_appear(): void
    {
        $this->makeWorkItem([
            'title' => 'In July',
            'planned_start_date' => '2026-07-05',
            'planned_end_date' => '2026-07-08',
        ]);
        $this->makeWorkItem([
            'title' => 'In August',
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-12',
        ]);

        $response = $this->actingAs($this->admin)->get('/calendar?date=2026-08-15');

        $titles = $this->flatItems($response)->pluck('title');
        $this->assertTrue($titles->contains('In August'));
        $this->assertFalse($titles->contains('In July'));
    }

    public function test_multi_day_work_items_span_their_date_range(): void
    {
        $this->makeWorkItem([
            'title' => 'Multi day work',
            'planned_start_date' => '2026-08-14',
            'planned_end_date' => '2026-08-20',
        ]);

        $response = $this->actingAs($this->admin)->get('/calendar?date=2026-08-15');

        $occurrences = collect($response->viewData('days'))
            ->flatMap(fn ($day) => $day['items'])
            ->where('title', 'Multi day work')
            ->count();

        // 14..20 is 7 days.
        $this->assertSame(7, $occurrences);
    }

    public function test_filters_work(): void
    {
        $this->makeWorkItem([
            'title' => 'Item A',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'status' => 'in_progress',
        ]);

        $this->makeWorkItem([
            'title' => 'Item B',
            'owner_id' => $this->spvB->id,
            'department_id' => $this->deptB->id,
            'area_id' => $this->areaB->id,
            'status' => 'blocked',
        ]);

        $response = $this->actingAs($this->admin)->get("/calendar?date=2026-08-15&department_id={$this->deptA->id}");
        $this->assertSame(['Item A'], $this->flatItems($response)->pluck('title')->all());

        $response = $this->actingAs($this->admin)->get("/calendar?date=2026-08-15&area_id={$this->areaB->id}");
        $this->assertSame(['Item B'], $this->flatItems($response)->pluck('title')->all());

        $response = $this->actingAs($this->admin)->get("/calendar?date=2026-08-15&owner_id={$this->spvA->id}");
        $this->assertSame(['Item A'], $this->flatItems($response)->pluck('title')->all());
    }

    public function test_search_filter_works(): void
    {
        $this->makeWorkItem(['title' => 'Alpha task']);
        $this->makeWorkItem(['title' => 'Beta task']);

        $response = $this->actingAs($this->admin)->get('/calendar?date=2026-08-15&search=Alpha');
        $this->assertSame(['Alpha task'], $this->flatItems($response)->pluck('title')->all());
    }

    public function test_status_filtering_works(): void
    {
        $this->makeWorkItem(['title' => 'Active task', 'status' => 'in_progress']);
        $this->makeWorkItem(['title' => 'Blocked task', 'status' => 'blocked']);

        $response = $this->actingAs($this->admin)->get('/calendar?date=2026-08-15&status=blocked');
        $this->assertSame(['Blocked task'], $this->flatItems($response)->pluck('title')->all());
    }

    public function test_null_area_items_remain_visible(): void
    {
        $this->makeWorkItem([
            'title' => 'Unassigned work',
            'area_id' => null,
        ]);

        $response = $this->actingAs($this->admin)->get('/calendar?date=2026-08-15');

        $this->assertTrue($this->flatItems($response)->pluck('title')->contains('Unassigned work'));
    }

    public function test_today_and_this_week_remain_functional(): void
    {
        $this->actingAs($this->admin)->get('/today')->assertStatus(200);
        $this->actingAs($this->admin)->get('/this-week')->assertStatus(200);
    }
}
