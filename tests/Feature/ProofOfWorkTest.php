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

class ProofOfWorkTest extends TestCase
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
     * CASE A: Create Daily Report with WorkItem without Proof URL.
     */
    public function test_create_daily_report_with_work_item_without_proof(): void
    {
        $date = '2026-08-29';

        $payload = [
            'reported_by' => $this->spvRiki->id,
            'report_date' => $date,
            'area_id' => $this->areaBahanBaku->id,
            'today_result' => 'Catatan harian',
            'work_items' => [
                [
                    'title' => 'PROSES BAHAN',
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'completed',
                    'proof_of_work_url' => '',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/daily-reports', $payload);
        $response->assertRedirect("/daily-reports?date={$date}");

        $item = WorkItem::where('owner_id', $this->spvRiki->id)->first();
        $this->assertNotNull($item);
        $this->assertNull($item->proof_of_work_url);
    }

    /**
     * CASE B: Create Daily Report with Proof URL.
     */
    public function test_create_daily_report_with_proof_url(): void
    {
        $date = '2026-08-29';
        $proofUrl = 'http://10.88.8.46:1001/photos/4919';

        $payload = [
            'reported_by' => $this->spvRiki->id,
            'report_date' => $date,
            'area_id' => $this->areaBahanBaku->id,
            'work_items' => [
                [
                    'title' => 'PROSES BAHAN',
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'completed',
                    'proof_of_work_url' => $proofUrl,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/daily-reports', $payload);
        $response->assertRedirect("/daily-reports?date={$date}");

        $item = WorkItem::where('owner_id', $this->spvRiki->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals($proofUrl, $item->proof_of_work_url);
    }

    /**
     * CASE C: Edit Daily Report and add Proof URL.
     */
    public function test_edit_daily_report_and_add_proof_url(): void
    {
        $date = '2026-08-29';
        $proofUrl = 'http://10.88.8.46:1001/photos/4919';

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
            'status' => 'completed',
            'source_daily_report_id' => $report->id,
            'proof_of_work_url' => null,
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
                    'proof_of_work_url' => $proofUrl,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put("/daily-reports/{$report->id}", $payload);
        $response->assertRedirect("/daily-reports?date={$date}");

        $item->refresh();
        $this->assertEquals($proofUrl, $item->proof_of_work_url);
    }

    /**
     * CASE D: Edit Daily Report and remove Proof URL.
     */
    public function test_edit_daily_report_and_remove_proof_url(): void
    {
        $date = '2026-08-29';
        $proofUrl = 'http://10.88.8.46:1001/photos/4919';

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
            'status' => 'completed',
            'source_daily_report_id' => $report->id,
            'proof_of_work_url' => $proofUrl,
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
                    'proof_of_work_url' => '',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put("/daily-reports/{$report->id}", $payload);
        $response->assertRedirect("/daily-reports?date={$date}");

        $item->refresh();
        $this->assertNull($item->proof_of_work_url);
    }

    /**
     * CASE E: Append Existing Daily Report with Proof URL.
     */
    public function test_append_existing_daily_report_with_proof_url(): void
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

        $item1 = WorkItem::create([
            'title' => 'PROSES BAHAN',
            'owner_id' => $this->spvRiki->id,
            'department_id' => $this->deptPpic->id,
            'area_id' => $this->areaBahanBaku->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'completed',
            'source_daily_report_id' => $report->id,
            'proof_of_work_url' => 'http://10.88.8.46:1001/photos/4919',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $item2 = WorkItem::create([
            'title' => 'POTONG 304',
            'owner_id' => $this->spvRiki->id,
            'department_id' => $this->deptPpic->id,
            'area_id' => $this->areaBahanBaku->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'in_progress',
            'source_daily_report_id' => $report->id,
            'proof_of_work_url' => null,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $item3 = WorkItem::create([
            'title' => 'PRESS 304, BESI',
            'owner_id' => $this->spvRiki->id,
            'department_id' => $this->deptPpic->id,
            'area_id' => $this->areaBahanBaku->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'completed',
            'source_daily_report_id' => $report->id,
            'proof_of_work_url' => 'http://10.88.8.46:1001/photos/4921',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Append 4th item with proof url
        $payload = [
            'work_items' => [
                [
                    'id' => $item1->id,
                    'title' => $item1->title,
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'completed',
                    'proof_of_work_url' => $item1->proof_of_work_url,
                ],
                [
                    'id' => $item2->id,
                    'title' => $item2->title,
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'in_progress',
                    'proof_of_work_url' => null,
                ],
                [
                    'id' => $item3->id,
                    'title' => $item3->title,
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'completed',
                    'proof_of_work_url' => $item3->proof_of_work_url,
                ],
                [
                    'id' => null,
                    'title' => 'PEKERJAAN KE-4 TAMBAHAN',
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'completed',
                    'proof_of_work_url' => 'http://10.88.8.46:1001/photos/4925',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->put("/daily-reports/{$report->id}", $payload);
        $response->assertRedirect("/daily-reports?date={$date}");

        $this->assertEquals(1, DailyReport::count());
        $this->assertEquals(4, WorkItem::where('source_daily_report_id', $report->id)->count());

        $item4 = WorkItem::where('title', 'PEKERJAAN KE-4 TAMBAHAN')->first();
        $this->assertNotNull($item4);
        $this->assertEquals('http://10.88.8.46:1001/photos/4925', $item4->proof_of_work_url);
    }

    /**
     * CASE F: Completed page with Proof URL renders active link.
     */
    public function test_completed_page_with_proof_url_renders_active_link(): void
    {
        $date = '2026-08-29';
        $proofUrl = 'http://10.88.8.46:1001/photos/4919';

        WorkItem::create([
            'title' => 'PROSES BAHAN SELESAI',
            'owner_id' => $this->spvRiki->id,
            'department_id' => $this->deptPpic->id,
            'area_id' => $this->areaBahanBaku->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'completed',
            'completed_at' => $date . ' 16:00:00',
            'proof_of_work_url' => $proofUrl,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/completed');
        $response->assertStatus(200);

        $response->assertSee('PROOF OF WORK');
        $response->assertSee('Buka Evidence');
        $response->assertSee($proofUrl, false);
        $response->assertSee('target="_blank"', false);
    }

    /**
     * CASE G: Completed page without Proof URL renders disabled button.
     */
    public function test_completed_page_without_proof_url_renders_disabled_button(): void
    {
        $date = '2026-08-29';

        WorkItem::create([
            'title' => 'PROSES BAHAN TANPA PROOF',
            'owner_id' => $this->spvRiki->id,
            'department_id' => $this->deptPpic->id,
            'area_id' => $this->areaBahanBaku->id,
            'original_start_date' => $date,
            'original_end_date' => $date,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'status' => 'completed',
            'completed_at' => $date . ' 16:00:00',
            'proof_of_work_url' => null,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/completed');
        $response->assertStatus(200);

        $response->assertSee('PROOF OF WORK');
        $response->assertSee('disabled', false);
        $response->assertSee('Proof');
    }

    /**
     * CASE H: Invalid URL fails validation.
     */
    public function test_invalid_proof_url_fails_validation(): void
    {
        $date = '2026-08-29';

        $payload = [
            'reported_by' => $this->spvRiki->id,
            'report_date' => $date,
            'area_id' => $this->areaBahanBaku->id,
            'work_items' => [
                [
                    'title' => 'PROSES BAHAN',
                    'planned_start_date' => $date,
                    'planned_end_date' => $date,
                    'status' => 'completed',
                    'proof_of_work_url' => 'not-a-valid-url',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post('/daily-reports', $payload);
        $response->assertSessionHasErrors('work_items.0.proof_of_work_url');
    }
}
