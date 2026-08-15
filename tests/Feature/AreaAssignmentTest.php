<?php

namespace Tests\Feature;

use App\Enums\Position;
use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function area(string $code = 'COR-FL'): Area
    {
        return Area::create(['code' => $code, 'name' => $code]);
    }

    public function test_assignment_can_be_created(): void
    {
        $area = $this->area();
        $user = User::factory()->create(['role' => 'spv']);

        $assignment = AreaAssignment::create([
            'area_id' => $area->id,
            'user_id' => $user->id,
            'role' => Position::Spv,
            'started_at' => '2026-01-01',
        ]);

        $this->assertTrue($assignment->area->is($area));
        $this->assertTrue($assignment->user->is($user));
        $this->assertSame(Position::Spv, $assignment->role);
    }

    public function test_multiple_users_can_share_one_area(): void
    {
        $area = $this->area();
        $kabag = User::factory()->create(['role' => 'kabag']);
        $spv = User::factory()->create(['role' => 'spv']);

        AreaAssignment::create([
            'area_id' => $area->id,
            'user_id' => $kabag->id,
            'role' => Position::Kabag,
            'started_at' => '2026-01-01',
        ]);
        AreaAssignment::create([
            'area_id' => $area->id,
            'user_id' => $spv->id,
            'role' => Position::Spv,
            'started_at' => '2026-01-01',
        ]);

        $this->assertCount(2, $area->assignments);
    }

    public function test_one_user_can_have_multiple_area_assignments(): void
    {
        $user = User::factory()->create(['role' => 'spv']);
        $flange = $this->area('NT-FL');
        $fitting = $this->area('NT-PF');

        AreaAssignment::create([
            'area_id' => $flange->id,
            'user_id' => $user->id,
            'role' => Position::Spv,
            'started_at' => '2026-01-01',
        ]);
        AreaAssignment::create([
            'area_id' => $fitting->id,
            'user_id' => $user->id,
            'role' => Position::Spv,
            'started_at' => '2026-01-01',
        ]);

        $this->assertCount(2, $user->areaAssignments);
        $this->assertCount(2, $user->assignedAreas);
    }

    public function test_ended_at_is_inclusive(): void
    {
        $area = $this->area();
        $user = User::factory()->create(['role' => 'spv']);

        $assignment = AreaAssignment::create([
            'area_id' => $area->id,
            'user_id' => $user->id,
            'role' => Position::Spv,
            'started_at' => '2026-01-01',
            'ended_at' => '2026-06-30',
        ]);

        $this->assertTrue($assignment->activeOn(Carbon::parse('2026-01-01')));
        $this->assertTrue($assignment->activeOn(Carbon::parse('2026-06-30')));
        $this->assertFalse($assignment->activeOn(Carbon::parse('2026-07-01')));
        $this->assertFalse($assignment->activeOn(Carbon::parse('2025-12-31')));
    }

    public function test_open_ended_assignment_is_active_indefinitely(): void
    {
        $area = $this->area();
        $user = User::factory()->create(['role' => 'spv']);

        $assignment = AreaAssignment::create([
            'area_id' => $area->id,
            'user_id' => $user->id,
            'role' => Position::Spv,
            'started_at' => '2026-01-01',
        ]);

        $this->assertNull($assignment->ended_at);
        $this->assertTrue($assignment->activeOn(Carbon::parse('2030-01-01')));
    }
}
