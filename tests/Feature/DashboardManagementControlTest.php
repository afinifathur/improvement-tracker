<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use App\Services\WorkingDayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardManagementControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $afin;
    protected User $bayu;
    protected Department $deptProd;
    protected Department $deptMaint;
    protected Area $areaMachining;
    protected Area $areaElectric;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptProd = Department::create(['name' => 'Produksi', 'code' => 'PROD', 'is_active' => true]);
        $this->deptMaint = Department::create(['name' => 'Maintenance', 'code' => 'MAINT', 'is_active' => true]);

        $this->areaMachining = Area::create(['name' => 'Area Machining', 'code' => 'MCH-01', 'department_id' => $this->deptProd->id, 'is_active' => true]);
        $this->areaElectric = Area::create(['name' => 'Area Listrik', 'code' => 'ELC-01', 'department_id' => $this->deptMaint->id, 'is_active' => true]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'department_id' => $this->deptProd->id,
            'is_active' => true,
        ]);

        $this->afin = User::create([
            'name' => 'AFIN',
            'email' => 'afin@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptProd->id,
            'is_active' => true,
        ]);
        AreaAssignment::create([
            'user_id' => $this->afin->id,
            'area_id' => $this->areaMachining->id,
            'role' => 'spv',
            'started_at' => '2026-01-01',
        ]);

        $this->bayu = User::create([
            'name' => 'BAYU',
            'email' => 'bayu@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptMaint->id,
            'is_active' => true,
        ]);
        AreaAssignment::create([
            'user_id' => $this->bayu->id,
            'area_id' => $this->areaElectric->id,
            'role' => 'spv',
            'started_at' => '2026-01-01',
        ]);
    }

    /**
     * Test 1: WorkingDayService 2-working-day grace period unit calculations.
     */
    public function test_1_working_day_service_grace_period_calculations(): void
    {
        // 1. Monday 2026-08-24 Deadline:
        // Tue 25 Aug (Grace 1) -> NOT overdue
        // Wed 26 Aug (Grace 2) -> NOT overdue
        // Thu 27 Aug (3rd working day) -> OVERDUE
        $this->assertFalse(WorkingDayService::isOverdueOn('2026-08-24', '2026-08-24'));
        $this->assertFalse(WorkingDayService::isOverdueOn('2026-08-24', '2026-08-25'));
        $this->assertFalse(WorkingDayService::isOverdueOn('2026-08-24', '2026-08-26'));
        $this->assertTrue(WorkingDayService::isOverdueOn('2026-08-24', '2026-08-27'));

        // 2. Friday 2026-08-28 Deadline:
        // Sat 29 Aug (Grace 1) -> NOT overdue
        // Sun 30 Aug (Sunday) -> NOT overdue
        // Mon 31 Aug (Grace 2) -> NOT overdue
        // Tue 01 Sep (3rd working day) -> OVERDUE
        $this->assertFalse(WorkingDayService::isOverdueOn('2026-08-28', '2026-08-28'));
        $this->assertFalse(WorkingDayService::isOverdueOn('2026-08-28', '2026-08-29'));
        $this->assertFalse(WorkingDayService::isOverdueOn('2026-08-28', '2026-08-30'));
        $this->assertFalse(WorkingDayService::isOverdueOn('2026-08-28', '2026-08-31'));
        $this->assertTrue(WorkingDayService::isOverdueOn('2026-08-28', '2026-09-01'));

        // 3. Saturday 2026-08-29 Deadline:
        // Sun 30 Aug (Sunday) -> NOT overdue
        // Mon 31 Aug (Grace 1) -> NOT overdue
        // Tue 01 Sep (Grace 2) -> NOT overdue
        // Wed 02 Sep (3rd working day) -> OVERDUE
        $this->assertFalse(WorkingDayService::isOverdueOn('2026-08-29', '2026-08-29'));
        $this->assertFalse(WorkingDayService::isOverdueOn('2026-08-29', '2026-08-30'));
        $this->assertFalse(WorkingDayService::isOverdueOn('2026-08-29', '2026-08-31'));
        $this->assertFalse(WorkingDayService::isOverdueOn('2026-08-29', '2026-09-01'));
        $this->assertTrue(WorkingDayService::isOverdueOn('2026-08-29', '2026-09-02'));
    }

    /**
     * Test 2: CRITICAL ACCEPTANCE CASE — AFIN with 5 WorkItems (deadline Friday 28 Aug 2026).
     * On Saturday 29 Aug 2026:
     * - Remaining = 5, Overdue = 0, On-time = 5.
     * - Chart bar: Entirely BLUE (Blue = 5, Red = 0).
     */
    public function test_2_afin_acceptance_case_friday_deadline_on_saturday_is_all_blue(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            WorkItem::create([
                'title' => "Tugas Afin {$i}",
                'owner_id' => $this->afin->id,
                'original_start_date' => '2026-08-24',
                'original_end_date' => '2026-08-28',
                'planned_start_date' => '2026-08-24',
                'planned_end_date' => '2026-08-28',
                'status' => 'in_progress',
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);
        }

        // 1. Check on Saturday 29 Aug 2026 (Grace day #1)
        $respSat = $this->actingAs($this->admin)->get(route('dashboard.index', ['date' => '2026-08-29']));
        $respSat->assertStatus(200);

        $this->assertEquals(5, $respSat->viewData('remainingWorkload'));
        $this->assertEquals(0, $respSat->viewData('overdueCount'));

        $afinWorkloadSat = collect($respSat->viewData('personnelWorkloads'))->firstWhere('user.name', 'AFIN');
        $this->assertNotNull($afinWorkloadSat);
        $this->assertEquals(5, $afinWorkloadSat['remaining']);
        $this->assertEquals(5, $afinWorkloadSat['on_time']);
        $this->assertEquals(0, $afinWorkloadSat['overdue']);

        // 2. Check on Monday 31 Aug 2026 (Grace day #2)
        $respMon = $this->actingAs($this->admin)->get(route('dashboard.index', ['date' => '2026-08-31']));
        $respMon->assertStatus(200);
        $this->assertEquals(0, $respMon->viewData('overdueCount'));

        $afinWorkloadMon = collect($respMon->viewData('personnelWorkloads'))->firstWhere('user.name', 'AFIN');
        $this->assertEquals(5, $afinWorkloadMon['remaining']);
        $this->assertEquals(5, $afinWorkloadMon['on_time']);
        $this->assertEquals(0, $afinWorkloadMon['overdue']);

        // 3. Check on Tuesday 01 Sep 2026 (3rd working day -> OVERDUE)
        $respTue = $this->actingAs($this->admin)->get(route('dashboard.index', ['date' => '2026-09-01']));
        $respTue->assertStatus(200);
        $this->assertEquals(5, $respTue->viewData('overdueCount'));

        $afinWorkloadTue = collect($respTue->viewData('personnelWorkloads'))->firstWhere('user.name', 'AFIN');
        $this->assertEquals(5, $afinWorkloadTue['remaining']);
        $this->assertEquals(0, $afinWorkloadTue['on_time']);
        $this->assertEquals(5, $afinWorkloadTue['overdue']);
    }

    /**
     * Test 3: If Admin updates the 5 items to completed before Tuesday,
     * they disappear from remaining and overdue.
     */
    public function test_3_afin_completed_items_disappear_from_remaining_and_overdue(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            WorkItem::create([
                'title' => "Tugas Afin {$i}",
                'owner_id' => $this->afin->id,
                'original_start_date' => '2026-08-24',
                'original_end_date' => '2026-08-28',
                'planned_start_date' => '2026-08-24',
                'planned_end_date' => '2026-08-28',
                'status' => 'completed',
                'completed_at' => '2026-08-29 14:00:00',
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('dashboard.index', ['date' => '2026-09-01']));
        $response->assertStatus(200);

        $this->assertEquals(0, $response->viewData('remainingWorkload'));
        $this->assertEquals(0, $response->viewData('overdueCount'));

        $afinWorkload = collect($response->viewData('personnelWorkloads'))->firstWhere('user.name', 'AFIN');
        $this->assertEquals(0, $afinWorkload['remaining']);
        $this->assertEquals(0, $afinWorkload['overdue']);
        $this->assertEquals(5, $afinWorkload['completed']);
    }

    /**
     * Test 4: Stacked bar chart data, deterministic segment ordering and sorting.
     */
    public function test_4_stacked_bar_chart_data_and_sorting(): void
    {
        // Afin: 3 open items (2 on-time, 1 overdue where deadline was Tuesday 2026-08-18)
        WorkItem::create([
            'title' => 'Afin On-time 1',
            'owner_id' => $this->afin->id,
            'original_start_date' => '2026-08-24',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-24',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        WorkItem::create([
            'title' => 'Afin On-time 2 Blocked',
            'owner_id' => $this->afin->id,
            'original_start_date' => '2026-08-24',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-24',
            'planned_end_date' => '2026-08-28',
            'status' => 'blocked',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        WorkItem::create([
            'title' => 'Afin Overdue',
            'owner_id' => $this->afin->id,
            'original_start_date' => '2026-08-10',
            'original_end_date' => '2026-08-18', // Tuesday 18 Aug -> Overdue by Friday 21 Aug
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-18',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Bayu: 1 open item (1 on-time, 0 overdue)
        WorkItem::create([
            'title' => 'Bayu Task',
            'owner_id' => $this->bayu->id,
            'original_start_date' => '2026-08-24',
            'original_end_date' => '2026-08-29',
            'planned_start_date' => '2026-08-24',
            'planned_end_date' => '2026-08-29',
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Check on Saturday 2026-08-29
        $response = $this->actingAs($this->admin)->get(route('dashboard.index', ['date' => '2026-08-29']));
        $response->assertStatus(200);

        $workloads = $response->viewData('personnelWorkloads');
        $this->assertCount(2, $workloads);

        // Sorting: Afin (3 remaining) appears first (leftmost), Bayu (1 remaining) second
        $this->assertEquals('AFIN', $workloads[0]['user']->name);
        $this->assertEquals('BAYU', $workloads[1]['user']->name);

        // Afin: remaining = 3, on_time = 2, overdue = 1 (no double counting!)
        $this->assertEquals(3, $workloads[0]['remaining']);
        $this->assertEquals(2, $workloads[0]['on_time']);
        $this->assertEquals(1, $workloads[0]['overdue']);
        $this->assertEquals(3, $workloads[0]['on_time'] + $workloads[0]['overdue']);

        // Bayu: remaining = 1, on_time = 1, overdue = 0
        $this->assertEquals(1, $workloads[1]['remaining']);
        $this->assertEquals(1, $workloads[1]['on_time']);
        $this->assertEquals(0, $workloads[1]['overdue']);
    }

    /**
     * Test 5: Drill down route check: clicking bar redirects to /person?person={id}
     */
    public function test_5_drill_down_person_route_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get(route('work-items.person', ['person' => $this->afin->id]));
        $response->assertStatus(200);
        $response->assertSee('AFIN');
    }

    /**
     * Test 6: Visual hierarchy layout ordering:
     * 1. SISA BEBAN KERJA PER PERSONEL
     * 2. TREN SISA BEBAN KERJA
     * 3. MATRIKS KEPATUHAN RENCANA HARIAN
     * 4. PEKERJAAN TERLAMBAT (OVERDUE) + PEKERJAAN TERBLOKIR (KENDALA)
     * 5. KEMAJUAN SASARAN MINGGUAN (WEEKLY PLANS PROGRESS) - STRICTLY BOTTOM-MOST
     */
    public function test_6_dashboard_layout_hierarchy_ordering(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard.index'));
        $response->assertStatus(200);

        $html = $response->getContent();

        $posWorkloadRanking = strpos($html, 'PERINGKAT SISA BEBAN KERJA PER PERSONEL');
        $posTrend = strpos($html, 'TREN SISA BEBAN KERJA (REMAINING WORKLOAD TREND)');
        $posMatrix = strpos($html, 'MATRIKS KEPATUHAN RENCANA HARIAN');
        $posOverdue = strpos($html, 'PEKERJAAN TERLAMBAT (OVERDUE)');
        $posBlocked = strpos($html, 'PEKERJAAN TERBLOKIR (KENDALA)');
        $posWeeklyPlans = strpos($html, 'KEMAJUAN SASARAN MINGGUAN (WEEKLY PLANS PROGRESS)');

        $this->assertNotFalse($posWorkloadRanking, 'Workload ranking section found');
        $this->assertNotFalse($posTrend, 'Trend section found');
        $this->assertNotFalse($posMatrix, 'Matrix compliance section found');
        $this->assertNotFalse($posOverdue, 'Overdue section found');
        $this->assertNotFalse($posBlocked, 'Blocked section found');
        $this->assertNotFalse($posWeeklyPlans, 'Weekly Plans section found');

        $this->assertTrue($posWorkloadRanking < $posTrend, 'Workload Ranking is before Trend');
        $this->assertTrue($posTrend < $posMatrix, 'Trend is before Matrix');
        $this->assertTrue($posMatrix < $posOverdue, 'Compliance Matrix is placed BEFORE Overdue section');
        $this->assertTrue($posMatrix < $posBlocked, 'Compliance Matrix is placed BEFORE Blocked section');
        $this->assertTrue($posOverdue < $posWeeklyPlans, 'Weekly Plans is placed AFTER Overdue section (Bottom-most)');
        $this->assertTrue($posBlocked < $posWeeklyPlans, 'Weekly Plans is placed AFTER Blocked section (Bottom-most)');
    }

    /**
     * Test 7: Weekly Plans table redesign:
     * - Progress calculations: 0/0 -> 0%, 2/4 -> 50%, 3/3 -> 100%
     * - Compact table headers and content rendering
     */
    public function test_7_weekly_plans_progress_table_rendering_and_calculations(): void
    {
        // 1. Plan A with 0 child work items (0/0 -> 0%)
        $planA = WeeklyPlan::create([
            'week_number' => 35,
            'week_start_date' => '2026-08-24',
            'week_end_date' => '2026-08-30',
            'user_id' => $this->afin->id,
            'title' => 'Sasaran Kaizen Afin',
            'expected_output' => 'Target selesai 100 unit',
            'category' => 'improvement',
            'impact_level' => 'high',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 2. Plan B with 4 child work items, 2 completed (2/4 -> 50%)
        $planB = WeeklyPlan::create([
            'week_number' => 35,
            'week_start_date' => '2026-08-24',
            'week_end_date' => '2026-08-30',
            'user_id' => $this->bayu->id,
            'title' => 'Sasaran Problem Bayu',
            'expected_output' => 'Selesaikan kebocoran oli',
            'category' => 'problem',
            'impact_level' => 'medium',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        for ($i = 1; $i <= 4; $i++) {
            WorkItem::create([
                'title' => "Child Item {$i}",
                'owner_id' => $this->bayu->id,
                'weekly_plan_id' => $planB->id,
                'original_start_date' => '2026-08-24',
                'original_end_date' => '2026-08-28',
                'planned_start_date' => '2026-08-24',
                'planned_end_date' => '2026-08-28',
                'status' => $i <= 2 ? 'completed' : 'in_progress',
                'completed_at' => $i <= 2 ? '2026-08-26 10:00:00' : null,
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('dashboard.index', ['date' => '2026-08-29']));
        $response->assertStatus(200);

        $weeklyPlansData = $response->viewData('weeklyPlans');
        $this->assertCount(2, $weeklyPlansData);

        $planAData = $weeklyPlansData->firstWhere('plan.id', $planA->id);
        $this->assertEquals(0, $planAData->total_items);
        $this->assertEquals(0, $planAData->completed_items);
        $this->assertEquals(0, $planAData->progress_percent);

        $planBData = $weeklyPlansData->firstWhere('plan.id', $planB->id);
        $this->assertEquals(4, $planBData->total_items);
        $this->assertEquals(2, $planBData->completed_items);
        $this->assertEquals(50, $planBData->progress_percent);

        // Verify Table Structure in HTML
        $response->assertSee('JUDUL SASARAN / RENCANA');
        $response->assertSee('TARGET SASARAN');
        $response->assertSee('SELESAI / TOTAL');
        $response->assertSee('0 / 0');
        $response->assertSee('2 / 4');
        $response->assertSee('50%');
    }
}
