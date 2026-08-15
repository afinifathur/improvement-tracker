<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_foreign_key_integrity_rejects_missing_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->expectException(QueryException::class);

        WorkItem::create([
            'title' => 'Ghost owned',
            'owner_id' => 999999,
            'original_start_date' => '2026-08-14',
            'original_end_date' => '2026-08-14',
            'planned_start_date' => '2026-08-14',
            'planned_end_date' => '2026-08-14',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    public function test_legacy_weekly_plan_still_works(): void
    {
        $spv = User::factory()->create(['role' => 'spv']);
        $admin = User::factory()->create(['role' => 'admin']);

        $plan = WeeklyPlan::create([
            'user_id' => $spv->id,
            'title' => 'Legacy plan',
            'expected_output' => 'Expected output for legacy plan',
            'category' => 'improvement',
            'impact_level' => 'medium',
            'week_start_date' => '2026-08-10',
            'week_end_date' => '2026-08-16',
            'status' => 'planned',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->assertTrue($plan->user->is($spv));

        $plan->status = 'completed';
        $plan->save();

        $this->assertNotNull($plan->score);
        $this->assertEquals(120.0, (float) $plan->score->final_score);
    }
}
