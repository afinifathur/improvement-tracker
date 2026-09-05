<?php

namespace Tests\Feature;

use App\Enums\BlockedReason;
use App\Enums\Position;
use App\Enums\WorkItemStatus;
use App\Enums\WorkType;
use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\Issue;
use App\Models\PlanScore;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use App\Models\WorkItemScheduleChange;
use App\Services\DailyMarkdownReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DailyMarkdownReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DailyMarkdownReportService $service;
    protected Department $department;
    protected Area $area;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DailyMarkdownReportService::class);

        $this->department = Department::create(['name' => 'Machining', 'code' => 'MCH', 'is_active' => true]);
        $this->area = Area::create(['name' => 'CNC Milling', 'code' => 'CNC-01', 'department_id' => $this->department->id, 'is_active' => true]);

        $this->user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        AreaAssignment::create([
            'area_id' => $this->area->id,
            'user_id' => $this->user->id,
            'role' => Position::Spv,
            'started_at' => '2026-08-01',
        ]);
    }

    public function test_generates_daily_snapshot_in_daily_mode(): void
    {
        Carbon::setTestNow('2026-09-05 10:00:00');
        $targetFile = storage_path('app/reports/2026-09-05.md');
        if (File::exists($targetFile)) {
            File::delete($targetFile);
        }

        // Create a daily report with associated work item
        $report = DailyReport::create([
            'report_date' => '2026-09-05',
            'reported_by' => $this->user->id,
            'area_id' => $this->area->id,
            'department_id' => $this->department->id,
            'today_result' => 'Pemesinan jig komponen selesai 100%.',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $item = WorkItem::create([
            'title' => 'Machining Housing Bushing',
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
            'area_id' => $this->area->id,
            'original_start_date' => '2026-09-05',
            'original_end_date' => '2026-09-05',
            'planned_start_date' => '2026-09-05',
            'planned_end_date' => '2026-09-05',
            'status' => WorkItemStatus::Completed,
            'completed_at' => '2026-09-05 14:30:00',
            'proof_of_work_url' => 'https://photos.app.goo.gl/evidence123',
            'source_daily_report_id' => $report->id,
            'work_type' => WorkType::Improvement,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $result = $this->service->generate('2026-09-05', true);

        $this->assertEquals('created', $result['status']);
        $this->assertEquals('2026-09-05', $result['date']);
        $this->assertEquals('DAILY', $result['mode']);
        $this->assertFileExists($result['file_path']);

        $content = $result['content'];
        $this->assertStringContainsString('# Daily Operational Snapshot — 2026-09-05', $content);
        $this->assertStringContainsString('Snapshot Mode**: `DAILY`', $content);
        $this->assertStringContainsString('Timezone**: Asia/Jakarta', $content);
        $this->assertStringContainsString('Pemesinan jig komponen selesai 100%.', $content);
        $this->assertStringContainsString('Machining Housing Bushing', $content);
        $this->assertStringContainsString('https://photos.app.goo.gl/evidence123', $content);
        $this->assertStringNotContainsString('Automated EOD', $content);
        $this->assertStringNotContainsString('EOD snapshot', $content);

        // Cleanup
        File::delete($result['file_path']);
    }

    public function test_generates_retroactive_reconstruction_mode_for_past_date(): void
    {
        Carbon::setTestNow('2026-09-05 10:00:00');

        $result = $this->service->generate('2026-09-01', true);

        $this->assertEquals('RETROACTIVE_RECONSTRUCTION', $result['mode']);
        $this->assertStringContainsString('Snapshot Mode**: `RETROACTIVE_RECONSTRUCTION`', $result['content']);
        $this->assertStringContainsString('This snapshot is a retroactive reconstruction', $result['content']);

        // Cleanup
        File::delete($result['file_path']);
    }

    public function test_renders_plain_markdown_without_html_entities_for_quotes_and_special_chars(): void
    {
        Carbon::setTestNow('2026-09-05 10:00:00');

        $report = DailyReport::create([
            'report_date' => '2026-09-05',
            'reported_by' => $this->user->id,
            'area_id' => $this->area->id,
            'department_id' => $this->department->id,
            'today_result' => 'Narrative with 6" pipe & 2" flange <Test>',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        WorkItem::create([
            'title' => 'Cutting 1 1/2" Shaft & "Special" Bushing',
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
            'area_id' => $this->area->id,
            'original_start_date' => '2026-09-05',
            'original_end_date' => '2026-09-05',
            'planned_start_date' => '2026-09-05',
            'planned_end_date' => '2026-09-05',
            'status' => WorkItemStatus::Completed,
            'completed_at' => '2026-09-05 14:30:00',
            'source_daily_report_id' => $report->id,
            'work_type' => WorkType::Routine,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $result = $this->service->generate('2026-09-05', true);
        $content = $result['content'];

        $this->assertStringContainsString('6" pipe & 2" flange <Test>', $content);
        $this->assertStringContainsString('Cutting 1 1/2" Shaft & "Special" Bushing', $content);
        $this->assertStringNotContainsString('&quot;', $content);
        $this->assertStringNotContainsString('&#039;', $content);

        // Cleanup
        File::delete($result['file_path']);
    }

    public function test_overdue_and_grace_period_classification_using_working_day_service(): void
    {
        // Reference date: Tuesday 1 Sep 2026
        Carbon::setTestNow('2026-09-01 10:00:00');

        // Friday 28 Aug item -> Saturday (Grace 1), Sunday (Off), Monday (Grace 2), Tuesday 1 Sep (Overdue)
        $overdueItem = WorkItem::create([
            'title' => 'Friday Item Overdue on Tuesday',
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
            'area_id' => $this->area->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => WorkItemStatus::InProgress,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        // Monday 31 Aug item -> Tuesday 1 Sep is Grace Day 1
        $graceItem = WorkItem::create([
            'title' => 'Monday Item In Grace on Tuesday',
            'owner_id' => $this->user->id,
            'department_id' => $this->department->id,
            'area_id' => $this->area->id,
            'original_start_date' => '2026-08-31',
            'original_end_date' => '2026-08-31',
            'planned_start_date' => '2026-08-31',
            'planned_end_date' => '2026-08-31',
            'status' => WorkItemStatus::InProgress,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $result = $this->service->generate('2026-09-01', true);
        $content = $result['content'];

        // Overdue register must contain Friday item
        $this->assertStringContainsString('Friday Item Overdue on Tuesday', $content);

        // Grace register must contain Monday item
        $this->assertStringContainsString('Monday Item In Grace on Tuesday', $content);
        $this->assertStringContainsString('Grace Day 1', $content);

        // Cleanup
        File::delete($result['file_path']);
    }

    public function test_existing_file_is_protected_without_force(): void
    {
        Carbon::setTestNow('2026-09-05 10:00:00');

        $reportsDir = storage_path('app/reports');
        if (! File::exists($reportsDir)) {
            File::makeDirectory($reportsDir, 0755, true);
        }
        $filePath = $reportsDir . '/2026-09-05.md';
        File::put($filePath, 'ORIGINAL SNAPSHOT CONTENT');

        // Call without force
        $result = $this->service->generate('2026-09-05', false);

        $this->assertEquals('exists', $result['status']);
        $this->assertEquals('ORIGINAL SNAPSHOT CONTENT', File::get($filePath));

        // Call with force
        $forceResult = $this->service->generate('2026-09-05', true);
        $this->assertEquals('overwritten', $forceResult['status']);
        $this->assertNotEquals('ORIGINAL SNAPSHOT CONTENT', File::get($filePath));

        // Cleanup
        File::delete($filePath);
    }
}
