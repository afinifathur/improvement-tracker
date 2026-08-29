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

class DailyReportDateNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $spvRiki;
    protected Department $deptPpic;
    protected Area $areaBahanBaku;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptPpic = Department::create(['name' => 'PPIC', 'code' => 'PPIC', 'is_active' => true]);

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

        $this->spvRiki = User::create([
            'name' => 'RIKI',
            'email' => 'riki@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptPpic->id,
            'is_active' => true,
        ]);

        AreaAssignment::create([
            'user_id' => $this->spvRiki->id,
            'area_id' => $this->areaBahanBaku->id,
            'role' => 'spv',
            'started_at' => '2026-08-01',
        ]);
    }

    /**
     * TEST 1: Navigate to previous date when report exists -> redirects to edit that report.
     */
    public function test_navigate_to_previous_date_when_report_exists_redirects_to_edit(): void
    {
        $report28 = DailyReport::create([
            'report_date' => '2026-08-28',
            'reported_by' => $this->spvRiki->id,
            'area_id' => $this->areaBahanBaku->id,
            'department_id' => $this->deptPpic->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $report29 = DailyReport::create([
            'report_date' => '2026-08-29',
            'reported_by' => $this->spvRiki->id,
            'area_id' => $this->areaBahanBaku->id,
            'department_id' => $this->deptPpic->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/daily-reports/navigate?person={$this->spvRiki->id}&date=2026-08-28&area_id={$this->areaBahanBaku->id}");
        $response->assertRedirect(route('daily-reports.edit', $report28));
    }

    /**
     * TEST 2: Navigate to next date when report exists -> redirects to edit that report.
     */
    public function test_navigate_to_next_date_when_report_exists_redirects_to_edit(): void
    {
        $report28 = DailyReport::create([
            'report_date' => '2026-08-28',
            'reported_by' => $this->spvRiki->id,
            'area_id' => $this->areaBahanBaku->id,
            'department_id' => $this->deptPpic->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $report29 = DailyReport::create([
            'report_date' => '2026-08-29',
            'reported_by' => $this->spvRiki->id,
            'area_id' => $this->areaBahanBaku->id,
            'department_id' => $this->deptPpic->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get("/daily-reports/navigate?person={$this->spvRiki->id}&date=2026-08-29&area_id={$this->areaBahanBaku->id}");
        $response->assertRedirect(route('daily-reports.edit', $report29));
    }

    /**
     * TEST 3: Target date has no report -> redirects cleanly to create with person and date.
     */
    public function test_navigate_when_no_report_exists_on_target_date_redirects_to_create(): void
    {
        $response = $this->actingAs($this->admin)->get("/daily-reports/navigate?person={$this->spvRiki->id}&date=2026-08-25&area_id={$this->areaBahanBaku->id}");
        $response->assertRedirect(route('daily-reports.create', [
            'person' => $this->spvRiki->id,
            'date' => '2026-08-25',
            'area_id' => $this->areaBahanBaku->id,
        ]));

        $createResponse = $this->actingAs($this->admin)->get(route('daily-reports.create', [
            'person' => $this->spvRiki->id,
            'date' => '2026-08-25',
            'area_id' => $this->areaBahanBaku->id,
        ]));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('RIKI');
        $createResponse->assertSee('2026-08-25');
    }

    /**
     * TEST 4 & 5: Edit page renders date navigation controls and loads existing items.
     */
    public function test_date_navigation_renders_controls_on_edit_page(): void
    {
        $date = '2026-08-29';

        $report = DailyReport::create([
            'report_date' => $date,
            'reported_by' => $this->spvRiki->id,
            'area_id' => $this->areaBahanBaku->id,
            'department_id' => $this->deptPpic->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $item = WorkItem::create([
            'title' => 'PROSES BAHAN',
            'owner_id' => $this->spvRiki->id,
            'department_id' => $this->deptPpic->id,
            'area_id' => $this->areaBahanBaku->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'in_progress',
            'source_daily_report_id' => $report->id,
            'proof_of_work_url' => 'http://10.88.8.46:1001/photos/4919',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('daily-reports.edit', $report));
        $response->assertStatus(200);

        $response->assertSee('Hari Sebelumnya');
        $response->assertSee('Hari Berikutnya');
        $response->assertSee('29 Aug 2026');
        $response->assertSee('PROSES BAHAN');
        $response->assertSee('http://10.88.8.46:1001/photos/4919', false);
    }

    /**
     * TEST 6 & 7: Admin updates status and Proof of Work URL.
     */
    public function test_admin_updates_status_and_proof_url_via_edit(): void
    {
        $date = '2026-08-29';

        $report = DailyReport::create([
            'report_date' => $date,
            'reported_by' => $this->spvRiki->id,
            'area_id' => $this->areaBahanBaku->id,
            'department_id' => $this->deptPpic->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $item = WorkItem::create([
            'title' => 'PROSES BAHAN',
            'owner_id' => $this->spvRiki->id,
            'department_id' => $this->deptPpic->id,
            'area_id' => $this->areaBahanBaku->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'in_progress',
            'source_daily_report_id' => $report->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $payload = [
            'work_items' => [
                [
                    'id' => $item->id,
                    'title' => 'PROSES BAHAN',
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'completed',
                    'proof_of_work_url' => 'http://10.88.8.46:1001/photos/4919',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('daily-reports.update', $report), $payload);
        $response->assertRedirect("/daily-reports?date={$date}");

        $item->refresh();
        $this->assertEquals('completed', $item->status->value);
        $this->assertEquals('http://10.88.8.46:1001/photos/4919', $item->proof_of_work_url);
    }
}
