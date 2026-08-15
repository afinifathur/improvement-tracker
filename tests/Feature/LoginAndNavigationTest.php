<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginAndNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => $role,
        ]);
    }

    public function test_admin_login_redirects_to_daily_reports(): void
    {
        $this->user('admin', 'admin@example.com');

        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect('/daily-reports');
    }

    public function test_director_login_redirects_to_daily_reports(): void
    {
        $this->user('director', 'director@example.com');

        $this->post('/login', ['email' => 'director@example.com', 'password' => 'password'])
            ->assertRedirect('/daily-reports');
    }

    public function test_manager_login_redirects_to_dashboard(): void
    {
        $this->user('manager', 'manager@example.com');

        $this->post('/login', ['email' => 'manager@example.com', 'password' => 'password'])
            ->assertRedirect('/');
    }

    public function test_admin_sees_daily_reports_navigation_item(): void
    {
        $admin = $this->user('admin', 'admin@example.com');

        $this->actingAs($admin)
            ->get(route('daily-reports.index'))
            ->assertSee(route('daily-reports.index'), false);
    }

    public function test_director_sees_daily_reports_navigation_item(): void
    {
        $director = $this->user('director', 'director@example.com');

        $this->actingAs($director)
            ->get(route('daily-reports.index'))
            ->assertSee(route('daily-reports.index'), false);
    }

    public function test_manager_does_not_see_daily_reports_navigation_item(): void
    {
        $manager = $this->user('manager', 'manager@example.com');
        $this->user('spv', 'spv@example.com');

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('daily-reports.index'), false);
    }

    public function test_legacy_routes_still_work(): void
    {
        $admin = $this->user('admin', 'admin@example.com');
        $this->user('spv', 'spv@example.com');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('rankings'))->assertOk();
        $this->actingAs($admin)->get(route('weekly-plans.create'))->assertOk();
        $this->actingAs($admin)->get(route('weekly-plans.closing'))->assertOk();
    }
}
