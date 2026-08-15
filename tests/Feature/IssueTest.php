<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Models\DailyReport;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_links_to_daily_reports_both_directions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'spv']);

        $issue = Issue::create([
            'title' => 'Frequent bearing failure on CNC-05',
            'first_reported_at' => now(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $report = DailyReport::create([
            'report_date' => '2026-08-14',
            'reported_by' => $reporter->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $report->issues()->attach($issue->id, ['note' => 'Still open', 'reported_at' => now()]);

        $this->assertTrue($report->issues->contains($issue));
        $this->assertTrue($issue->dailyReports->contains($report));
    }

    public function test_issue_status_values(): void
    {
        $values = array_map(fn ($status) => $status->value, IssueStatus::cases());

        $this->assertEqualsCanonicalizing(['open', 'resolved', 'closed'], $values);
    }

    public function test_issue_defaults_to_open(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $issue = Issue::create([
            'title' => 'Bearing failure',
            'first_reported_at' => now(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->assertSame(IssueStatus::Open, $issue->status);
    }
}
