<?php

namespace Tests\Feature;

use App\Enums\Position;
use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\User;
use App\Services\ComplianceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ComplianceService $service;
    protected Department $department;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ComplianceService::class);

        $this->department = Department::create(['name' => 'Produksi', 'code' => 'PRD', 'is_active' => true]);
        $this->area = Area::create(['name' => 'Machining', 'code' => 'MCH-01', 'department_id' => $this->department->id, 'is_active' => true]);
    }

    public function test_evaluates_compliance_for_active_historical_personnel(): void
    {
        Carbon::setTestNow('2026-09-05 10:00:00');

        $user1 = User::create(['name' => 'User One', 'email' => 'user1@test.com', 'password' => bcrypt('password'), 'role' => 'spv', 'is_active' => true]);
        $user2 = User::create(['name' => 'User Two', 'email' => 'user2@test.com', 'password' => bcrypt('password'), 'role' => 'spv', 'is_active' => true]);

        AreaAssignment::create(['area_id' => $this->area->id, 'user_id' => $user1->id, 'role' => Position::Spv, 'started_at' => '2026-09-01']);
        AreaAssignment::create(['area_id' => $this->area->id, 'user_id' => $user2->id, 'role' => Position::Spv, 'started_at' => '2026-09-01']);

        // Only user1 submits
        DailyReport::create([
            'report_date' => '2026-09-05',
            'reported_by' => $user1->id,
            'area_id' => $this->area->id,
            'department_id' => $this->department->id,
            'today_result' => 'Hasil kerja selesai.',
            'created_by' => $user1->id,
            'updated_by' => $user1->id,
        ]);

        $result = $this->service->evaluateDailyCompliance('2026-09-05');

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['submitted']);
        $this->assertEquals(50, $result['percent']);
        $this->assertTrue($result['missing']->contains('User Two'));
        $this->assertFalse($result['missing']->contains('User One'));

        $this->assertCount(2, $result['details']);
        $detail1 = $result['details']->firstWhere('name', 'User One');
        $this->assertEquals('Submitted', $detail1->status);
        $detail2 = $result['details']->firstWhere('name', 'User Two');
        $this->assertEquals('Missing', $detail2->status);
    }

    public function test_handles_configured_reporting_pairs(): void
    {
        Carbon::setTestNow('2026-09-05 10:00:00');

        config(['reporting.temporary_reporting_pairs' => [
            ['bagus@test.com', 'sahrul@test.com'],
        ]]);

        $userA = User::create(['name' => 'Bagus', 'email' => 'bagus@test.com', 'password' => bcrypt('password'), 'role' => 'spv', 'is_active' => true]);
        $userB = User::create(['name' => 'Sahrul', 'email' => 'sahrul@test.com', 'password' => bcrypt('password'), 'role' => 'spv', 'is_active' => true]);

        AreaAssignment::create(['area_id' => $this->area->id, 'user_id' => $userA->id, 'role' => Position::Spv, 'started_at' => '2026-09-01']);
        AreaAssignment::create(['area_id' => $this->area->id, 'user_id' => $userB->id, 'role' => Position::Spv, 'started_at' => '2026-09-01']);

        // Only Bagus submits, fulfilling the pair obligation
        DailyReport::create([
            'report_date' => '2026-09-05',
            'reported_by' => $userA->id,
            'area_id' => $this->area->id,
            'department_id' => $this->department->id,
            'today_result' => 'Laporan shift.',
            'created_by' => $userA->id,
            'updated_by' => $userA->id,
        ]);

        $result = $this->service->evaluateDailyCompliance('2026-09-05');

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['submitted']);
        $this->assertEquals(100, $result['percent']);
        $this->assertCount(0, $result['missing']);

        $pairDetail = $result['details']->firstWhere('name', 'Bagus / Sahrul');
        $this->assertNotNull($pairDetail);
        $this->assertEquals('Submitted', $pairDetail->status);
    }
}
