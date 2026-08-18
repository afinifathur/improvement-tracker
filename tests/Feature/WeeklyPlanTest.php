<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\Department;
use App\Models\User;
use App\Models\WeeklyPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyPlanTest extends TestCase
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

    private function operationalPersonnel(string $name, int $assignmentCount = 1): User
    {
        $dept = Department::create(['code' => 'D-'.$name, 'name' => 'Dept '.$name]);

        $user = User::create([
            'name' => $name,
            'email' => strtolower($name).'@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $dept->id,
            'department_name' => $dept->name,
        ]);

        for ($i = 0; $i < $assignmentCount; $i++) {
            $area = Area::create([
                'code' => 'A-'.$name.'-'.$i,
                'name' => 'Area '.$name.' '.$i,
                'department_id' => $dept->id,
            ]);

            AreaAssignment::create([
                'area_id' => $area->id,
                'user_id' => $user->id,
                'role' => 'spv',
                'started_at' => '2026-01-01',
            ]);
        }

        return $user;
    }

    private function planPayload(User $owner, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $owner->id,
            'title' => 'Opname dan penataan gudang',
            'expected_output' => 'Rak A-1 sampai A-5 selesai diopname',
            'category' => 'improvement',
            'impact_level' => 'medium',
            'week_start_date' => '2026-08-17',
        ], $overrides);
    }

    public function test_week_34_persists_monday_to_sunday(): void
    {
        $dani = $this->operationalPersonnel('DANI');

        $this->actingAs($this->admin)
            ->post(route('api.weekly-plans.store'), $this->planPayload($dani, ['week_start_date' => '2026-08-17']))
            ->assertRedirect(route('weekly-plans.closing'));

        $plan = WeeklyPlan::first();
        $this->assertSame('2026-08-17', $plan->week_start_date->toDateString());
        $this->assertSame('2026-08-23', $plan->week_end_date->toDateString());
    }

    public function test_non_monday_date_is_normalized_to_monday(): void
    {
        $dani = $this->operationalPersonnel('DANI');

        $this->actingAs($this->admin)
            ->post(route('api.weekly-plans.store'), $this->planPayload($dani, ['week_start_date' => '2026-08-19']))
            ->assertRedirect(route('weekly-plans.closing'));

        $plan = WeeklyPlan::first();
        $this->assertSame('2026-08-17', $plan->week_start_date->toDateString());
        $this->assertSame('2026-08-23', $plan->week_end_date->toDateString());
    }

    public function test_client_week_end_date_is_ignored(): void
    {
        $dani = $this->operationalPersonnel('DANI');

        $this->actingAs($this->admin)
            ->post(route('api.weekly-plans.store'), $this->planPayload($dani, [
                'week_start_date' => '2026-08-17',
                'week_end_date' => '2026-08-17',
            ]))
            ->assertRedirect(route('weekly-plans.closing'));

        $plan = WeeklyPlan::first();
        $this->assertSame('2026-08-17', $plan->week_start_date->toDateString());
        $this->assertSame('2026-08-23', $plan->week_end_date->toDateString());
        $this->assertGreaterThan($plan->week_start_date, $plan->week_end_date);
    }

    public function test_invalid_date_is_rejected(): void
    {
        $dani = $this->operationalPersonnel('DANI');

        $this->actingAs($this->admin)
            ->post(route('api.weekly-plans.store'), $this->planPayload($dani, ['week_start_date' => 'not-a-date']))
            ->assertSessionHasErrors('week_start_date');

        $this->assertSame(0, WeeklyPlan::count());
    }

    public function test_create_form_redirects_and_flashes(): void
    {
        $dani = $this->operationalPersonnel('DANI');

        $this->actingAs($this->admin)
            ->post(route('api.weekly-plans.store'), $this->planPayload($dani))
            ->assertRedirect(route('weekly-plans.closing'))
            ->assertSessionHas('status', 'Rencana mingguan berhasil dibuat.');
    }

    public function test_api_store_returns_json(): void
    {
        $dani = $this->operationalPersonnel('DANI');

        $this->actingAs($this->admin)
            ->post(route('api.weekly-plans.store'), $this->planPayload($dani), ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Weekly plan created successfully.');
    }

    public function test_operational_personnel_appear_in_selector(): void
    {
        $this->operationalPersonnel('DANI');

        $response = $this->actingAs($this->admin)->get(route('weekly-plans.create'));
        $personnel = $response->viewData('personnel');

        $this->assertTrue($personnel->contains('name', 'DANI'));
    }

    public function test_supervisor_a_is_excluded_from_selector(): void
    {
        $this->operationalPersonnel('DANI');

        User::create([
            'name' => 'Supervisor A',
            'email' => 'spv_a@kaizen.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
        ]);

        $response = $this->actingAs($this->admin)->get(route('weekly-plans.create'));
        $personnel = $response->viewData('personnel');

        $this->assertFalse($personnel->contains('name', 'Supervisor A'));
    }

    public function test_huda_appears_once_despite_two_assignments(): void
    {
        $this->operationalPersonnel('HUDA', 2);

        $response = $this->actingAs($this->admin)->get(route('weekly-plans.create'));
        $personnel = $response->viewData('personnel');

        $this->assertSame(1, $personnel->where('name', 'HUDA')->count());
    }

    public function test_non_operational_user_id_is_rejected(): void
    {
        $supervisorA = User::create([
            'name' => 'Supervisor A',
            'email' => 'spv_a@kaizen.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
        ]);

        $this->actingAs($this->admin)
            ->post(route('api.weekly-plans.store'), $this->planPayload($supervisorA))
            ->assertSessionHasErrors('user_id');

        $this->assertSame(0, WeeklyPlan::count());
    }
}
