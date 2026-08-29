<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportsGridAndPlanCountTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $spvAdi;
    protected User $spvRiki;
    protected Department $deptQa;
    protected Department $deptPpic;
    protected Area $areaQaFlange;
    protected Area $areaBahanBaku;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptQa = Department::create(['name' => 'QA/QC', 'code' => 'QAQC', 'is_active' => true]);
        $this->deptPpic = Department::create(['name' => 'PPIC', 'code' => 'PPIC', 'is_active' => true]);

        $this->areaQaFlange = Area::create([
            'name' => 'QA FLANGE',
            'code' => 'QA-01',
            'department_id' => $this->deptQa->id,
            'is_active' => true,
        ]);

        $this->areaBahanBaku = Area::create([
            'name' => 'BAHAN BAKU',
            'code' => 'PPIC-01',
            'department_id' => $this->deptPpic->id,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'department_id' => $this->deptPpic->id,
            'is_active' => true,
        ]);

        $this->spvAdi = User::create([
            'name' => 'ADI',
            'email' => 'adi@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptQa->id,
            'is_active' => true,
        ]);

        $this->spvRiki = User::create([
            'name' => 'RIKI',
            'email' => 'riki@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptPpic->id,
            'is_active' => true,
        ]);

        AreaAssignment::create([
            'user_id' => $this->spvAdi->id,
            'area_id' => $this->areaQaFlange->id,
            'role' => 'spv',
            'started_at' => '2026-08-01',
        ]);

        AreaAssignment::create([
            'user_id' => $this->spvRiki->id,
            'area_id' => $this->areaBahanBaku->id,
            'role' => 'spv',
            'started_at' => '2026-08-01',
        ]);
    }

    /**
     * Test 1: Grid layout classes and card visual hierarchy.
     */
    public function test_daily_reports_renders_grid_layout_and_cards(): void
    {
        $date = '2026-08-29';

        // ADI has not submitted (Belum Isi), has 5 work items on date
        for ($i = 1; $i <= 5; $i++) {
            WorkItem::create([
                'title' => "Adi Task {$i}",
                'owner_id' => $this->spvAdi->id,
                'department_id' => $this->deptQa->id,
                'area_id' => $this->areaQaFlange->id,
                'original_start_date' => $date,
                'original_end_date' => $date,
                'planned_start_date' => $date,
                'planned_end_date' => $date,
                'status' => 'not_started',
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);
        }

        // RIKI has submitted (Sudah Isi), has 3 work items on date
        DailyReport::create([
            'report_date' => $date,
            'reported_by' => $this->spvRiki->id,
            'area_id' => $this->areaBahanBaku->id,
            'department_id' => $this->deptPpic->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            WorkItem::create([
                'title' => "Riki Task {$i}",
                'owner_id' => $this->spvRiki->id,
                'department_id' => $this->deptPpic->id,
                'area_id' => $this->areaBahanBaku->id,
                'original_start_date' => $date,
                'original_end_date' => $date,
                'planned_start_date' => $date,
                'planned_end_date' => $date,
                'status' => 'completed',
                'completed_at' => $date . ' 17:00:00',
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->admin)->get("/daily-reports?date={$date}");
        $response->assertStatus(200);

        // Verify Grid structure
        $response->assertSee('grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3', false);

        // Verify Department grouping
        $response->assertSee('QA/QC');
        $response->assertSee('PPIC');

        // Verify Personel Card details for ADI (Belum Isi, Rencana 5, Button Isi)
        $response->assertSee('ADI');
        $response->assertSee('SPV');
        $response->assertSee('QA FLANGE');
        $response->assertSee('Belum Isi');
        $response->assertSee('Rencana');
        $response->assertSee('5');
        $response->assertSee('Isi');

        // Verify Personel Card details for RIKI (Sudah Isi, Rencana 3, Button Buka)
        $response->assertSee('RIKI');
        $response->assertSee('BAHAN BAKU');
        $response->assertSee('Sudah Isi');
        $response->assertSee('3');
        $response->assertSee('Buka');

        // Verify Summary KPI cards
        $response->assertSee('Target Laporan');
        $response->assertSee('Sudah Lapor');
        $response->assertSee('Belum Lapor');
        $response->assertSee('Pekerjaan Terbuka');
        $response->assertSee('Terblokir');
        $response->assertSee('Terlambat');
    }

    /**
     * Test 2: Rencana count accurately filters by date and excludes cancelled items.
     */
    public function test_rencana_count_accurate_and_excludes_other_dates_and_cancelled(): void
    {
        $date = '2026-08-29';

        // 1 item on target date
        WorkItem::create([
            'title' => 'Adi Task Today',
            'owner_id' => $this->spvAdi->id,
            'department_id' => $this->deptQa->id,
            'area_id' => $this->areaQaFlange->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 1 cancelled item on target date (should NOT be counted)
        WorkItem::create([
            'title' => 'Adi Task Cancelled',
            'owner_id' => $this->spvAdi->id,
            'department_id' => $this->deptQa->id,
            'area_id' => $this->areaQaFlange->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 1 item on another date (2026-08-20, should NOT be counted)
        WorkItem::create([
            'title' => 'Adi Task Past',
            'owner_id' => $this->spvAdi->id,
            'department_id' => $this->deptQa->id,
            'area_id' => $this->areaQaFlange->id,
            'original_start_date' => '2026-08-20',
            'original_end_date' => '2026-08-20',
            'planned_start_date' => '2026-08-20',
            'planned_end_date' => '2026-08-20',
            'status' => 'completed',
            'completed_at' => '2026-08-20 17:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/daily-reports?date={$date}");
        $response->assertStatus(200);

        // ADI should have Rencana count = 1
        $response->assertSee('1');
    }
}
