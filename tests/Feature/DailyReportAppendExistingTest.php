<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportAppendExistingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $spvRiki;
    protected User $spvAdi;
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

        $this->spvAdi = User::create([
            'name' => 'ADI',
            'email' => 'adi@test.com',
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
     * CASE A (Part 1): Options endpoint detects existing report and returns its work items.
     */
    public function test_options_endpoint_returns_existing_report_and_work_items_when_report_exists(): void
    {
        $date = '2026-08-29';

        $report = DailyReport::create([
            'report_date' => $date,
            'reported_by' => $this->spvRiki->id,
            'area_id' => $this->areaBahanBaku->id,
            'department_id' => $this->deptPpic->id,
            'today_result' => 'Catatan pekerjaan harian',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $itemTitles = ['PROSES BAHAN', 'POTONG 304', 'PRESS 304, BESI'];
        foreach ($itemTitles as $title) {
            WorkItem::create([
                'title' => $title,
                'owner_id' => $this->spvRiki->id,
                'department_id' => $this->deptPpic->id,
                'area_id' => $this->areaBahanBaku->id,
                'original_start_date' => $date,
                'original_end_date' => $date,
                'planned_start_date' => $date,
                'planned_end_date' => $date,
                'status' => 'completed',
                'source_daily_report_id' => $report->id,
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->admin)->getJson("/api/users/{$this->spvRiki->id}/daily-report-options?date={$date}");
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'areas',
            'has_active_assignments',
            'weekly_plans',
            'existing_report' => [
                'id',
                'report_date',
                'area_id',
                'area_name',
                'today_result',
                'edit_url',
                'update_url',
                'work_items' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'weekly_plan_id',
                        'planned_start_date',
                        'planned_end_date',
                        'status',
                    ],
                ],
            ],
        ]);

        $data = $response->json('existing_report');
        $this->assertEquals($report->id, $data['id']);
        $this->assertEquals($this->areaBahanBaku->id, $data['area_id']);
        $this->assertEquals('BAHAN BAKU', $data['area_name']);
        $this->assertCount(3, $data['work_items']);
        $this->assertEquals('PROSES BAHAN', $data['work_items'][0]['title']);
    }

    /**
     * CASE B: Options endpoint returns existing_report = null when no report exists.
     */
    public function test_options_endpoint_returns_null_existing_report_when_no_report_exists(): void
    {
        $date = '2026-08-29';

        $response = $this->actingAs($this->admin)->getJson("/api/users/{$this->spvAdi->id}/daily-report-options?date={$date}");
        $response->assertStatus(200);

        $this->assertNull($response->json('existing_report'));
    }

    /**
     * CASE A (Part 2): Append mode persists new work item to existing report without duplicating report.
     */
    public function test_append_mode_persists_new_work_item_to_existing_report_without_duplicate_report(): void
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

        $existingItems = [];
        $itemTitles = ['PROSES BAHAN', 'POTONG 304', 'PRESS 304, BESI'];
        foreach ($itemTitles as $title) {
            $existingItems[] = WorkItem::create([
                'title' => $title,
                'owner_id' => $this->spvRiki->id,
                'department_id' => $this->deptPpic->id,
                'area_id' => $this->areaBahanBaku->id,
                'original_start_date' => $date,
                'original_end_date' => $date,
                'planned_start_date' => $date,
                'planned_end_date' => $date,
                'status' => 'completed',
                'source_daily_report_id' => $report->id,
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);
        }

        // Submitting with existing 3 items (with IDs) + 1 new item (without ID)
        $payload = [
            'today_result' => 'Updated daily notes',
            'work_items' => [
                [
                    'id' => $existingItems[0]->id,
                    'title' => $existingItems[0]->title,
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'completed',
                ],
                [
                    'id' => $existingItems[1]->id,
                    'title' => $existingItems[1]->title,
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'completed',
                ],
                [
                    'id' => $existingItems[2]->id,
                    'title' => $existingItems[2]->title,
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'completed',
                ],
                [
                    'id' => null,
                    'title' => 'PEKERJAAN KE-4 TAMBAHAN',
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'in_progress',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put("/daily-reports/{$report->id}", $payload);
        $response->assertRedirect("/daily-reports?date={$date}");

        // Assert only 1 Daily Report exists
        $this->assertEquals(1, DailyReport::where('reported_by', $this->spvRiki->id)->whereDate('report_date', $date)->count());

        // Assert 4 total WorkItems exist
        $items = WorkItem::where('owner_id', $this->spvRiki->id)->where('source_daily_report_id', $report->id)->get();
        $this->assertCount(4, $items);

        $newItem = $items->firstWhere('title', 'PEKERJAAN KE-4 TAMBAHAN');
        $this->assertNotNull($newItem);
        $this->assertEquals('in_progress', $newItem->status->value);
        $this->assertEquals($report->id, $newItem->source_daily_report_id);
    }

    /**
     * CASE C: Append mode works when existing report has 0 work items.
     */
    public function test_append_mode_when_existing_report_has_zero_work_items(): void
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

        $response = $this->actingAs($this->admin)->getJson("/api/users/{$this->spvRiki->id}/daily-report-options?date={$date}");
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('existing_report.work_items'));

        // Append first work item
        $payload = [
            'work_items' => [
                [
                    'id' => null,
                    'title' => 'PEKERJAAN PERTAMA',
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'not_started',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put("/daily-reports/{$report->id}", $payload);
        $response->assertRedirect("/daily-reports?date={$date}");

        $this->assertEquals(1, DailyReport::count());
        $this->assertEquals(1, WorkItem::where('source_daily_report_id', $report->id)->count());
    }

    /**
     * CASE H: Direct edit page (/daily-reports/{id}/edit) works normally.
     */
    public function test_edit_page_behavior_is_preserved(): void
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

        $response = $this->actingAs($this->admin)->get("/daily-reports/{$report->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('Ubah Laporan');
        $response->assertSee('RIKI');
        $response->assertSee('BAHAN BAKU');
    }
}
