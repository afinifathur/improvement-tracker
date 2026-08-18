<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function director(): User
    {
        return User::factory()->create(['role' => 'director']);
    }

    private function manager(): User
    {
        return User::factory()->create(['role' => 'manager']);
    }

    private function reporter(?int $deptId = null, string $name = 'Budi', string $role = 'spv'): User
    {
        return User::factory()->create([
            'role' => $role,
            'department_id' => $deptId,
            'name' => $name,
        ]);
    }

    private function area(?int $deptId = null): Area
    {
        return Area::create([
            'code' => 'A-'.str_replace('.', '', uniqid('', true)),
            'name' => 'Test Area',
            'department_id' => $deptId,
        ]);
    }

    private function makeWorkItem(int $ownerId, int $adminId, array $overrides = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'title' => 'Sample work item',
            'owner_id' => $ownerId,
            'original_start_date' => '2026-08-10',
            'original_end_date' => '2026-08-12',
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-12',
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ], $overrides));
    }

    public function test_admin_can_access_control_center(): void
    {
        $this->actingAs($this->admin())->get(route('daily-reports.index'))->assertOk();
    }

    public function test_director_can_access_control_center_read_only(): void
    {
        $this->actingAs($this->director())->get(route('daily-reports.index'))->assertOk();
    }

    public function test_manager_cannot_access_control_center(): void
    {
        $this->actingAs($this->manager())->get(route('daily-reports.index'))->assertForbidden();
    }

    public function test_control_center_summary_counts(): void
    {
        $dept = Department::create(['name' => 'Production', 'code' => 'PRD']);

        $spv1 = $this->reporter($dept->id, 'Budi');
        $this->reporter($dept->id, 'Andi');
        $this->reporter($dept->id, 'Joko', 'kabag');

        $this->admin();
        $this->director();

        $admin = $this->admin();

        DailyReport::create([
            'report_date' => now()->toDateString(),
            'reported_by' => $spv1->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('daily-reports.index'));
        $summary = $response->viewData('summary');

        $this->assertSame(3, $summary['expected']);
        $this->assertSame(1, $summary['processed']);
        $this->assertSame(2, $summary['remaining']);
    }

    public function test_control_center_groups_reporters_by_department(): void
    {
        $production = Department::create(['name' => 'Production', 'code' => 'PRD']);
        $maintenance = Department::create(['name' => 'Maintenance', 'code' => 'MNT']);

        $this->reporter($production->id, 'Budi');
        $this->reporter($maintenance->id, 'Dedi');

        $response = $this->actingAs($this->admin())->get(route('daily-reports.index'));
        $grouped = $response->viewData('grouped');

        $this->assertTrue($grouped->has('Production'));
        $this->assertTrue($grouped->has('Maintenance'));
        $this->assertCount(1, $grouped['Production']);
        $this->assertCount(1, $grouped['Maintenance']);
    }

    public function test_admin_can_open_daily_report_entry(): void
    {
        $spv = $this->reporter();

        $this->actingAs($this->admin())
            ->get(route('daily-reports.create', ['person' => $spv->id, 'date' => '2026-08-14']))
            ->assertOk();
    }

    public function test_director_cannot_open_daily_report_entry(): void
    {
        $spv = $this->reporter();

        $this->actingAs($this->director())
            ->get(route('daily-reports.create', ['person' => $spv->id, 'date' => '2026-08-14']))
            ->assertForbidden();
    }

    public function test_existing_report_redirects_to_edit(): void
    {
        $admin = $this->admin();
        $spv = $this->reporter();

        $report = DailyReport::create([
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('daily-reports.create', ['person' => $spv->id, 'date' => '2026-08-14']))
            ->assertRedirect(route('daily-reports.edit', $report));
    }

    public function test_active_work_items_are_loaded(): void
    {
        $admin = $this->admin();
        $spv = $this->reporter();

        $this->makeWorkItem($spv->id, $admin->id, [
            'status' => 'in_progress',
            'planned_start_date' => '2026-08-13',
            'planned_end_date' => '2026-08-15',
            'original_start_date' => '2026-08-13',
            'original_end_date' => '2026-08-15',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('daily-reports.create', ['person' => $spv->id, 'date' => '2026-08-14']));

        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems['current']);
    }

    public function test_completed_and_cancelled_work_items_are_excluded(): void
    {
        $admin = $this->admin();
        $spv = $this->reporter();

        $this->makeWorkItem($spv->id, $admin->id, [
            'title' => 'In progress',
            'status' => 'in_progress',
            'planned_start_date' => '2026-08-13',
            'planned_end_date' => '2026-08-15',
        ]);
        $this->makeWorkItem($spv->id, $admin->id, [
            'title' => 'Completed',
            'status' => 'completed',
        ]);
        $this->makeWorkItem($spv->id, $admin->id, [
            'title' => 'Cancelled',
            'status' => 'cancelled',
            'cancel_reason' => 'no_longer_required',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('daily-reports.create', ['person' => $spv->id, 'date' => '2026-08-14']));

        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems['current']);
        $this->assertCount(0, $workItems['overdue']);
        $this->assertCount(0, $workItems['future']);
    }

    public function test_work_items_are_classified_overdue_current_future(): void
    {
        $admin = $this->admin();
        $spv = $this->reporter();

        $this->makeWorkItem($spv->id, $admin->id, [
            'title' => 'Overdue',
            'status' => 'in_progress',
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-12',
        ]);
        $this->makeWorkItem($spv->id, $admin->id, [
            'title' => 'Current',
            'status' => 'not_started',
            'planned_start_date' => '2026-08-13',
            'planned_end_date' => '2026-08-15',
        ]);
        $this->makeWorkItem($spv->id, $admin->id, [
            'title' => 'Future',
            'status' => 'blocked',
            'blocked_reason' => 'waiting_sparepart',
            'planned_start_date' => '2026-08-15',
            'planned_end_date' => '2026-08-20',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('daily-reports.create', ['person' => $spv->id, 'date' => '2026-08-14']));

        $workItems = $response->viewData('workItems');

        $this->assertSame('Overdue', $workItems['overdue'][0]->title);
        $this->assertSame('Current', $workItems['current'][0]->title);
        $this->assertSame('Future', $workItems['future'][0]->title);
    }

    public function test_admin_can_create_daily_report(): void
    {
        $dept = Department::create(['name' => 'Production', 'code' => 'PRD']);
        $spv = $this->reporter($dept->id);
        $area = $this->area($dept->id);

        $response = $this->actingAs($this->admin())->post(route('daily-reports.store'), [
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
            'area_id' => $area->id,
            'today_result' => 'CNC-05 back online.',
        ]);

        $response->assertRedirect(route('daily-reports.index', ['date' => '2026-08-14']));

        $this->assertSame(1, DailyReport::where('reported_by', $spv->id)
            ->whereDate('report_date', '2026-08-14')
            ->count());
    }

    public function test_daily_report_stores_audit_fields(): void
    {
        $dept = Department::create(['name' => 'Production', 'code' => 'PRD']);
        $spv = $this->reporter($dept->id);
        $admin = $this->admin();
        $area = $this->area($dept->id);

        $this->actingAs($admin)->post(route('daily-reports.store'), [
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
            'area_id' => $area->id,
        ]);

        $report = DailyReport::first();

        $this->assertSame($spv->id, $report->reported_by);
        $this->assertSame($admin->id, $report->created_by);
        $this->assertSame($admin->id, $report->updated_by);
        $this->assertNotSame($report->reported_by, $report->created_by);
    }

    public function test_new_work_items_are_created_with_the_report(): void
    {
        $dept = Department::create(['name' => 'Production', 'code' => 'PRD']);
        $spv = $this->reporter($dept->id);
        $area = $this->area($dept->id);

        $this->actingAs($this->admin())->post(route('daily-reports.store'), [
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
            'area_id' => $area->id,
            'work_items' => [
                [
                    'title' => 'Task A',
                    'planned_start_date' => '2026-08-15',
                    'planned_end_date' => '2026-08-15',
                ],
                [
                    'title' => 'Task B',
                    'description' => 'Follow up',
                    'planned_start_date' => '2026-08-15',
                    'planned_end_date' => '2026-08-20',
                ],
            ],
        ]);

        $report = DailyReport::first();

        $this->assertSame(2, $report->workItems()->count());
        $this->assertSame(2, WorkItem::where('source_daily_report_id', $report->id)->count());
    }

    public function test_new_work_item_receives_derived_fields(): void
    {
        $dept = Department::create(['name' => 'Maintenance', 'code' => 'MNT']);
        $spv = $this->reporter($dept->id);
        $admin = $this->admin();
        $area = $this->area($dept->id);

        $this->actingAs($admin)->post(route('daily-reports.store'), [
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
            'area_id' => $area->id,
            'work_items' => [
                [
                    'title' => 'Replace bearing',
                    'description' => 'CNC-05',
                    'planned_start_date' => '2026-08-15',
                    'planned_end_date' => '2026-08-20',
                ],
            ],
        ]);

        $report = DailyReport::first();
        $item = WorkItem::first();

        $this->assertSame($spv->id, $item->owner_id);
        $this->assertSame($area->id, $item->area_id);
        $this->assertSame($dept->id, $item->department_id);
        $this->assertSame($report->id, $item->source_daily_report_id);
        $this->assertSame('2026-08-15', $item->original_start_date->toDateString());
        $this->assertSame('2026-08-20', $item->original_end_date->toDateString());
        $this->assertSame('2026-08-15', $item->planned_start_date->toDateString());
        $this->assertSame('2026-08-20', $item->planned_end_date->toDateString());
        $this->assertSame($admin->id, $item->created_by);
        $this->assertSame($admin->id, $item->updated_by);
        $this->assertSame('not_started', $item->status->value);
    }

    public function test_new_work_item_default_date_equals_report_date(): void
    {
        $spv = $this->reporter();

        $response = $this->actingAs($this->admin())
            ->get(route('daily-reports.create', ['person' => $spv->id, 'date' => '2026-08-14']));

        $this->assertSame('2026-08-14', $response->viewData('defaultDate'));
    }

    public function test_invalid_work_item_prevents_report_creation(): void
    {
        $spv = $this->reporter();
        $area = $this->area();

        $this->actingAs($this->admin())->post(route('daily-reports.store'), [
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
            'area_id' => $area->id,
            'work_items' => [
                [
                    'title' => '',
                    'planned_start_date' => '2026-08-15',
                    'planned_end_date' => '2026-08-15',
                ],
            ],
        ])->assertSessionHasErrors('work_items.0.title');

        $this->assertDatabaseCount('daily_reports', 0);
        $this->assertDatabaseCount('work_items', 0);
    }

    public function test_get_daily_report_options_endpoint_returns_json(): void
    {
        $spv = $this->reporter();
        $area = $this->area();
        $admin = $this->admin();

        // Assign area to spv
        \App\Models\AreaAssignment::create([
            'area_id' => $area->id,
            'user_id' => $spv->id,
            'role' => 'spv',
            'started_at' => '2026-08-01',
        ]);

        $response = $this->actingAs($admin)->getJson(route('api.users.daily-report-options', [
            'user' => $spv->id,
            'date' => '2026-08-14'
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'areas' => [
                '*' => ['id', 'name']
            ],
            'has_active_assignments',
            'weekly_plans'
        ]);
    }

    public function test_update_daily_report_saves_existing_and_creates_new_work_items(): void
    {
        $dept = Department::create(['name' => 'Production', 'code' => 'PRD']);
        $spv = $this->reporter($dept->id);
        $admin = $this->admin();
        $area = $this->area($dept->id);

        $report = DailyReport::create([
            'report_date' => '2026-08-14',
            'reported_by' => $spv->id,
            'area_id' => $area->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $item = WorkItem::create([
            'title' => 'Existing Task',
            'owner_id' => $spv->id,
            'area_id' => $area->id,
            'department_id' => $dept->id,
            'original_start_date' => '2026-08-14',
            'original_end_date' => '2026-08-14',
            'planned_start_date' => '2026-08-14',
            'planned_end_date' => '2026-08-14',
            'source_daily_report_id' => $report->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->put(route('daily-reports.update', $report), [
            'today_result' => 'Updated result',
            'work_items' => [
                [
                    'id' => $item->id,
                    'title' => 'Updated Task Title',
                    'planned_start_date' => '2026-08-14',
                    'planned_end_date' => '2026-08-14',
                    'status' => 'completed',
                ],
                [
                    'title' => 'Newly Added Task',
                    'planned_start_date' => '2026-08-14',
                    'planned_end_date' => '2026-08-14',
                    'status' => 'in_progress',
                ],
            ]
        ]);

        $response->assertRedirect(route('daily-reports.index', ['date' => '2026-08-14']));

        $item->refresh();
        $this->assertSame('Updated Task Title', $item->title);
        $this->assertSame('completed', $item->status->value);
        $this->assertNotNull($item->completed_at);

        $this->assertDatabaseHas('work_items', [
            'title' => 'Newly Added Task',
            'status' => 'in_progress',
            'source_daily_report_id' => $report->id,
        ]);
    }
}
