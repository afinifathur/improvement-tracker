<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateDailyReportCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $dept = Department::create(['name' => 'Machining', 'code' => 'MCH', 'is_active' => true]);
        Area::create(['name' => 'CNC Milling', 'code' => 'CNC-01', 'department_id' => $dept->id, 'is_active' => true]);
    }

    public function test_command_generates_snapshot_for_default_today(): void
    {
        Carbon::setTestNow('2026-09-05 10:00:00');

        $filePath = storage_path('app/reports/2026-09-05.md');
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        $this->artisan('report:generate-daily')
            ->expectsOutputToContain('Generating Daily Markdown Snapshot for: 2026-09-05')
            ->expectsOutputToContain('Snapshot successfully generated!')
            ->assertSuccessful();

        $this->assertFileExists($filePath);
        $this->assertStringContainsString('Snapshot Mode**: `DAILY`', File::get($filePath));

        File::delete($filePath);
    }

    public function test_command_generates_snapshot_for_explicit_date(): void
    {
        Carbon::setTestNow('2026-09-05 10:00:00');

        $filePath = storage_path('app/reports/2026-09-01.md');
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        $this->artisan('report:generate-daily', ['date' => '2026-09-01'])
            ->expectsOutputToContain('Generating Daily Markdown Snapshot for: 2026-09-01')
            ->expectsOutputToContain('Snapshot successfully generated!')
            ->assertSuccessful();

        $this->assertFileExists($filePath);
        $this->assertStringContainsString('Snapshot Mode**: `RETROACTIVE_RECONSTRUCTION`', File::get($filePath));

        File::delete($filePath);
    }

    public function test_command_protects_existing_file_unless_force(): void
    {
        Carbon::setTestNow('2026-09-05 10:00:00');

        $reportsDir = storage_path('app/reports');
        if (! File::exists($reportsDir)) {
            File::makeDirectory($reportsDir, 0755, true);
        }
        $filePath = $reportsDir . '/2026-09-05.md';
        File::put($filePath, 'PROTECTED CONTENT');

        // First run without --force: should warn and preserve
        $this->artisan('report:generate-daily', ['date' => '2026-09-05'])
            ->expectsOutputToContain('already exists. Use --force to regenerate.')
            ->assertSuccessful();

        $this->assertEquals('PROTECTED CONTENT', File::get($filePath));

        // Second run with --force: should overwrite
        $this->artisan('report:generate-daily', ['date' => '2026-09-05', '--force' => true])
            ->expectsOutputToContain('Snapshot successfully generated!')
            ->assertSuccessful();

        $this->assertNotEquals('PROTECTED CONTENT', File::get($filePath));
        $this->assertStringContainsString('# Daily Operational Snapshot — 2026-09-05', File::get($filePath));

        File::delete($filePath);
    }
}
