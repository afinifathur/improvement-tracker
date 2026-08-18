<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function personnel(string $name): User
    {
        $dept = Department::create(['code' => 'D-'.$name, 'name' => 'Dept '.$name]);
        $area = Area::create(['code' => 'A-'.$name, 'name' => 'Area '.$name, 'department_id' => $dept->id]);

        $user = User::create([
            'name' => $name,
            'email' => strtolower($name).'@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $dept->id,
        ]);

        AreaAssignment::create([
            'area_id' => $area->id,
            'user_id' => $user->id,
            'role' => 'spv',
            'started_at' => '2026-01-01',
        ]);

        return $user;
    }

    private function makeWorkItem(User $owner, string $status, string $start = '2099-01-01', string $end = '2099-01-01'): void
    {
        WorkItem::create([
            'title' => 'Task '.$status,
            'owner_id' => $owner->id,
            'department_id' => $owner->department_id,
            'original_start_date' => $start,
            'original_end_date' => $end,
            'planned_start_date' => $start,
            'planned_end_date' => $end,
            'status' => $status,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    private function makeDailyReport(User $reporter, string $date): void
    {
        DailyReport::create([
            'report_date' => $date,
            'reported_by' => $reporter->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_accepts_admin_manager_director(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')->assertOk();

        $manager = User::create(['name' => 'Manager', 'email' => 'mgr@test.com', 'password' => bcrypt('password'), 'role' => 'manager']);
        $this->actingAs($manager)->get('/dashboard')->assertOk();

        $director = User::create(['name' => 'Director', 'email' => 'dir@test.com', 'password' => bcrypt('password'), 'role' => 'director']);
        $this->actingAs($director)->get('/dashboard')->assertOk();

        $spv = User::create(['name' => 'SPV', 'email' => 'spv@test.com', 'password' => bcrypt('password'), 'role' => 'spv']);
        $this->actingAs($spv)->get('/dashboard')->assertForbidden();
    }

    public function test_workload_is_grouped_and_sorted_descending(): void
    {
        $afin = $this->personnel('AFIN');
        $herman = $this->personnel('HERMAN');
        $riki = $this->personnel('RIKI');

        foreach (range(1, 3) as $i) {
            $this->makeWorkItem($afin, 'in_progress');
        }
        foreach (range(1, 5) as $i) {
            $this->makeWorkItem($herman, 'blocked');
        }
        foreach (range(1, 2) as $i) {
            $this->makeWorkItem($riki, 'not_started');
        }

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $rows = $response->viewData('rows');

        $this->assertSame(['HERMAN', 'AFIN', 'RIKI'], $rows->pluck('name')->all());
        $this->assertSame([5, 3, 2], $rows->pluck('count')->all());
    }

    public function test_completed_and_cancelled_are_not_counted_as_unfinished(): void
    {
        $edi = $this->personnel('EDI');

        $this->makeWorkItem($edi, 'not_started');
        $this->makeWorkItem($edi, 'in_progress');
        $this->makeWorkItem($edi, 'blocked');
        $this->makeWorkItem($edi, 'completed');
        $this->makeWorkItem($edi, 'cancelled');

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $rows = $response->viewData('rows');

        $this->assertSame(3, $rows->first()['count']);
    }

    public function test_zero_workload_personnel_appear_on_the_right(): void
    {
        $ika = $this->personnel('IKA');
        $this->personnel('NISA');

        $this->makeWorkItem($ika, 'in_progress');

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $rows = $response->viewData('rows');

        $this->assertSame(['IKA', 'NISA'], $rows->pluck('name')->all());
        $this->assertSame([1, 0], $rows->pluck('count')->all());
    }

    public function test_kpi_totals_are_correct(): void
    {
        $huda = $this->personnel('HUDA');

        $this->makeWorkItem($huda, 'not_started');
        $this->makeWorkItem($huda, 'in_progress');
        $this->makeWorkItem($huda, 'blocked', '2020-01-01', '2020-01-01');
        $this->makeWorkItem($huda, 'completed');

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $kpis = $response->viewData('kpis');

        $this->assertSame(4, $kpis['total']);
        $this->assertSame(3, $kpis['unfinished']);
        $this->assertSame(1, $kpis['overdue']);
        $this->assertSame(1, $kpis['completed']);
    }

    public function test_system_accounts_without_area_assignments_are_excluded(): void
    {
        $real = $this->personnel('AFIN');
        $this->makeWorkItem($real, 'in_progress');

        $system = User::create(['name' => 'System Account', 'email' => 'sys@test.com', 'password' => bcrypt('password'), 'role' => 'spv']);
        $this->makeWorkItem($system, 'in_progress');

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $rows = $response->viewData('rows');

        $this->assertCount(1, $rows);
        $this->assertSame('AFIN', $rows->first()['name']);
    }

    public function test_existing_weekly_plan_dashboard_is_preserved(): void
    {
        $this->actingAs($this->admin)->get('/')->assertRedirect(route('dashboard.index'));
    }

    public function test_compliance_matrix_has_monday_to_sunday(): void
    {
        $this->personnel('AFIN');

        $response = $this->actingAs($this->admin)->get('/dashboard?date=2026-08-14');
        $days = $response->viewData('days');

        $this->assertCount(7, $days);

        $this->assertSame('2026-08-10', $days[0]['dateStr']);
        $this->assertSame('2026-08-16', $days[6]['dateStr']);

        $weekdays = collect($days)->pluck('weekday')->all();
        $this->assertSame(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'], $weekdays);
    }

    public function test_each_day_has_independent_percentage(): void
    {
        $afin = $this->personnel('AFIN');
        $eko = $this->personnel('EKO');

        $this->makeDailyReport($afin, '2026-08-10');
        $this->makeDailyReport($afin, '2026-08-11');
        $this->makeDailyReport($eko, '2026-08-10');

        $response = $this->actingAs($this->admin)->get('/dashboard?date=2026-08-14');
        $days = collect($response->viewData('days'))->keyBy('dateStr');

        $this->assertSame(100, $days['2026-08-10']['percent']);
        $this->assertSame(50, $days['2026-08-11']['percent']);
        $this->assertSame(0, $days['2026-08-12']['percent']);
    }

    public function test_current_day_is_highlighted(): void
    {
        $this->personnel('AFIN');

        $today = now()->toDateString();

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $days = collect($response->viewData('days'));

        $this->assertTrue($days->contains(fn ($day) => $day['isToday'] === true && $day['dateStr'] === $today));
    }

    public function test_missing_today_personnel_are_listed_dynamically(): void
    {
        $afin = $this->personnel('AFIN');
        $eko = $this->personnel('EKO');

        $today = now()->toDateString();
        $this->makeDailyReport($afin, $today);

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $missing = $response->viewData('missingToday');

        $this->assertContains('EKO', $missing->all());
        $this->assertNotContains('AFIN', $missing->all());
    }

    public function test_weekly_navigation_changes_selected_week(): void
    {
        $this->personnel('AFIN');

        $response = $this->actingAs($this->admin)->get('/dashboard?date=2026-08-14');
        $this->assertSame('2026-08-10', $response->viewData('weekStart')->toDateString());

        $next = $this->actingAs($this->admin)->get('/dashboard?date=2026-08-21');
        $this->assertSame('2026-08-17', $next->viewData('weekStart')->toDateString());
    }
}
