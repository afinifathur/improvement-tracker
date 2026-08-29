<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Department;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThisWeekManagementOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $spvAfin;
    protected User $spvHerman;
    protected User $spvIka;
    protected Department $deptPpic;
    protected Area $areaPpic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptPpic = Department::create(['name' => 'PPIC', 'code' => 'PPIC', 'is_active' => true]);
        $this->areaPpic = Area::create(['name' => 'PPIC CONTROL', 'code' => 'PPIC-01', 'department_id' => $this->deptPpic->id, 'is_active' => true]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'department_id' => $this->deptPpic->id,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'department_id' => $this->deptPpic->id,
            'is_active' => true,
        ]);

        $this->spvAfin = User::create([
            'name' => 'AFIN',
            'email' => 'afin@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptPpic->id,
            'is_active' => true,
        ]);

        $this->spvHerman = User::create([
            'name' => 'HERMAN',
            'email' => 'herman@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptPpic->id,
            'is_active' => true,
        ]);

        $this->spvIka = User::create([
            'name' => 'IKA',
            'email' => 'ika@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptPpic->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Authorization and view accessibility.
     */
    public function test_this_week_view_authorization(): void
    {
        $resGuest = $this->get('/this-week');
        $resGuest->assertRedirect('/login');

        $resAdmin = $this->actingAs($this->admin)->get('/this-week');
        $resAdmin->assertStatus(200);

        $resManager = $this->actingAs($this->manager)->get('/this-week');
        $resManager->assertStatus(200);
    }

    /**
     * Test 2: Table Overview rendering with columns, progress calculation, and Detail modal.
     */
    public function test_table_overview_and_detail_modal_rendering(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00'); // Week 35: Mon 24 Aug - Sun 30 Aug 2026

        // 1. IKA: 0 / 0 work items
        $planIka = WeeklyPlan::create([
            'user_id' => $this->spvIka->id,
            'title' => 'PLAN MINGGU DEPAN',
            'expected_output' => 'Estimasi kirim barang',
            'category' => 'improvement',
            'impact_level' => 'medium',
            'week_start_date' => '2026-08-24',
            'week_end_date' => '2026-08-30',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 2. AFIN: 2 / 4 completed
        $planAfin = WeeklyPlan::create([
            'user_id' => $this->spvAfin->id,
            'title' => 'SELESAIKAN SYSTEM FITTING',
            'expected_output' => 'Selesai hingga oven',
            'category' => 'problem',
            'impact_level' => 'medium',
            'week_start_date' => '2026-08-24',
            'week_end_date' => '2026-08-30',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 2 completed items for Afin
        WorkItem::create([
            'title' => 'AJARI PPIC DAN CEK HASIL INPUTAN CETAK',
            'owner_id' => $this->spvAfin->id,
            'department_id' => $this->deptPpic->id,
            'area_id' => $this->areaPpic->id,
            'weekly_plan_id' => $planAfin->id,
            'original_start_date' => '2026-08-25',
            'original_end_date' => '2026-08-25',
            'planned_start_date' => '2026-08-25',
            'planned_end_date' => '2026-08-25',
            'status' => 'completed',
            'completed_at' => '2026-08-25 17:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        WorkItem::create([
            'title' => 'UPDATE KODE DAN BAG DI SYSTEM FITTING',
            'owner_id' => $this->spvAfin->id,
            'department_id' => $this->deptPpic->id,
            'area_id' => $this->areaPpic->id,
            'weekly_plan_id' => $planAfin->id,
            'original_start_date' => '2026-08-25',
            'original_end_date' => '2026-08-25',
            'planned_start_date' => '2026-08-25',
            'planned_end_date' => '2026-08-25',
            'status' => 'completed',
            'completed_at' => '2026-08-25 17:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        // 2 in-progress items for Afin
        WorkItem::create([
            'title' => 'TAMBAH MODUL DAN TEST WORKFLOW QC DI SYSTEM FITTING',
            'owner_id' => $this->spvAfin->id,
            'department_id' => $this->deptPpic->id,
            'area_id' => $this->areaPpic->id,
            'weekly_plan_id' => $planAfin->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        WorkItem::create([
            'title' => 'CEK DAN AJARI ULANG PPIC',
            'owner_id' => $this->spvAfin->id,
            'department_id' => $this->deptPpic->id,
            'area_id' => $this->areaPpic->id,
            'weekly_plan_id' => $planAfin->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 3. HERMAN: 3 / 3 completed
        $planHerman = WeeklyPlan::create([
            'user_id' => $this->spvHerman->id,
            'title' => 'TARGET KIRIM LOKAL 10 TON',
            'expected_output' => 'Target kirim local tepat waktu',
            'category' => 'improvement',
            'impact_level' => 'high',
            'week_start_date' => '2026-08-24',
            'week_end_date' => '2026-08-30',
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            WorkItem::create([
                'title' => "Herman Task {$i}",
                'owner_id' => $this->spvHerman->id,
                'department_id' => $this->deptPpic->id,
                'area_id' => $this->areaPpic->id,
                'weekly_plan_id' => $planHerman->id,
                'original_start_date' => '2026-08-26',
                'original_end_date' => '2026-08-26',
                'planned_start_date' => '2026-08-26',
                'planned_end_date' => '2026-08-26',
                'status' => 'completed',
                'completed_at' => '2026-08-26 17:00:00',
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/this-week');
        $response->assertStatus(200);

        // Verify Table Headers
        $response->assertSee('PENANGGUNG JAWAB');
        $response->assertSee('DEPARTEMEN');
        $response->assertSee('JENIS');
        $response->assertSee('SASARAN / RENCANA');
        $response->assertSee('TARGET');
        $response->assertSee('PROGRESS');
        $response->assertSee('STATUS');
        $response->assertSee('AKSI');

        // Verify Table Row Content
        $response->assertSee('IKA');
        $response->assertSee('AFIN');
        $response->assertSee('HERMAN');
        $response->assertSee('PLAN MINGGU DEPAN');
        $response->assertSee('SELESAIKAN SYSTEM FITTING');
        $response->assertSee('TARGET KIRIM LOKAL 10 TON');

        // Verify Progress in Table and 100% Bar Rendering
        $response->assertSee('0 / 0');
        $response->assertSee('2 / 4');
        $response->assertSee('3 / 3');
        $response->assertSee('50%');
        $response->assertSee('100%');
        $response->assertSee('style="width: 100%;"', false);
        $response->assertSee('style="width: 50%;"', false);
        $response->assertSee('style="width: 0%;"', false);

        // Verify TARGET wrapping and AKSI stability classes
        $response->assertSee('whitespace-normal break-words', false);
        $response->assertSee('whitespace-nowrap', false);

        // Verify DETAIL action buttons
        $response->assertSee("openPlanDetail({$planIka->id})", false);
        $response->assertSee("openPlanDetail({$planAfin->id})", false);
        $response->assertSee("openPlanDetail({$planHerman->id})", false);

        // Verify Detail Modals exist in DOM
        $response->assertSee("id=\"plan-detail-modal-{$planIka->id}\"", false);
        $response->assertSee("id=\"plan-detail-modal-{$planAfin->id}\"", false);
        $response->assertSee("id=\"plan-detail-modal-{$planHerman->id}\"", false);

        // Verify Empty state message inside Ika's modal
        $response->assertSee('BELUM ADA PEKERJAAN HARIAN YANG DITAUTKAN KE RENCANA INI');

        // Verify Linked daily items inside Afin's modal
        $response->assertSee('AJARI PPIC DAN CEK HASIL INPUTAN CETAK');
        $response->assertSee('UPDATE KODE DAN BAG DI SYSTEM FITTING');
        $response->assertSee('TAMBAH MODUL DAN TEST WORKFLOW QC DI SYSTEM FITTING');
        $response->assertSee('CEK DAN AJARI ULANG PPIC');

        // Verify Evaluation buttons exist for Admin
        $response->assertSee('openEvaluationModal');
        $response->assertSee('openExtensionModal');
    }

    /**
     * Test 3: Filters work with Table Overview.
     */
    public function test_filters_work_with_table_overview(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00');

        WeeklyPlan::create([
            'user_id' => $this->spvIka->id,
            'title' => 'Ika Plan',
            'expected_output' => 'Output 1',
            'category' => 'improvement',
            'impact_level' => 'low',
            'week_start_date' => '2026-08-24',
            'week_end_date' => '2026-08-30',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        WeeklyPlan::create([
            'user_id' => $this->spvAfin->id,
            'title' => 'Afin Plan',
            'expected_output' => 'Output 2',
            'category' => 'problem',
            'impact_level' => 'medium',
            'week_start_date' => '2026-08-24',
            'week_end_date' => '2026-08-30',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Filter by Afin
        $response = $this->actingAs($this->admin)->get('/this-week?owner_id=' . $this->spvAfin->id);
        $response->assertStatus(200);
        $response->assertSee('Afin Plan');
        $response->assertDontSee('Ika Plan');
    }

    /**
     * Test 4: Week Navigation retains correct Weekly Plans.
     */
    public function test_week_navigation(): void
    {
        // Past week (Week 34: 17 Aug - 23 Aug)
        WeeklyPlan::create([
            'user_id' => $this->spvAfin->id,
            'title' => 'Week 34 Plan',
            'expected_output' => 'Output 34',
            'category' => 'improvement',
            'impact_level' => 'medium',
            'week_start_date' => '2026-08-17',
            'week_end_date' => '2026-08-23',
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Current week (Week 35: 24 Aug - 30 Aug)
        WeeklyPlan::create([
            'user_id' => $this->spvAfin->id,
            'title' => 'Week 35 Plan',
            'expected_output' => 'Output 35',
            'category' => 'problem',
            'impact_level' => 'medium',
            'week_start_date' => '2026-08-24',
            'week_end_date' => '2026-08-30',
            'status' => 'planned',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $resWeek34 = $this->actingAs($this->admin)->get('/this-week?date=2026-08-19');
        $resWeek34->assertStatus(200);
        $resWeek34->assertSee('Week 34 Plan');
        $resWeek34->assertDontSee('Week 35 Plan');

        $resWeek35 = $this->actingAs($this->admin)->get('/this-week?date=2026-08-26');
        $resWeek35->assertStatus(200);
        $resWeek35->assertSee('Week 35 Plan');
        $resWeek35->assertDontSee('Week 34 Plan');
    }
}
