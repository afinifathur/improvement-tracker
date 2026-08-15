<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssuesViewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $deptA;

    private Department $deptB;

    private Area $areaA;

    private Area $areaB;

    private User $spvA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->deptA = Department::create(['code' => 'D-A', 'name' => 'Dept A']);
        $this->deptB = Department::create(['code' => 'D-B', 'name' => 'Dept B']);

        $this->areaA = Area::create(['code' => 'AR-A', 'name' => 'Area A', 'department_id' => $this->deptA->id]);
        $this->areaB = Area::create(['code' => 'AR-B', 'name' => 'Area B', 'department_id' => $this->deptB->id]);

        $this->spvA = User::create([
            'name' => 'Supervisor A',
            'email' => 'spva@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptA->id,
        ]);
    }

    private function makeIssue(array $overrides = []): Issue
    {
        return Issue::create(array_merge([
            'title' => 'Sample issue',
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'status' => 'open',
            'first_reported_at' => '2026-08-14 08:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_issues_route_requires_authentication(): void
    {
        $this->get('/issues')->assertRedirect('/login');
    }

    public function test_issues_route_accepts_admin_manager_director(): void
    {
        $this->actingAs($this->admin)->get('/issues')->assertStatus(200);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'mgr@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);
        $this->actingAs($manager)->get('/issues')->assertStatus(200);

        $director = User::create([
            'name' => 'Director User',
            'email' => 'dir@test.com',
            'password' => bcrypt('password'),
            'role' => 'director',
        ]);
        $this->actingAs($director)->get('/issues')->assertStatus(200);

        $this->actingAs($this->spvA)->get('/issues')->assertStatus(403);
    }

    public function test_issues_are_listed(): void
    {
        $this->makeIssue(['title' => 'CNC 03 unavailable']);
        $this->makeIssue(['title' => 'Material certificate missing']);

        $response = $this->actingAs($this->admin)->get('/issues');
        $response->assertStatus(200);

        $issues = $response->viewData('issues');
        $this->assertCount(2, $issues);
        $this->assertTrue($issues->pluck('title')->contains('CNC 03 unavailable'));
        $this->assertTrue($issues->pluck('title')->contains('Material certificate missing'));
    }

    public function test_status_filtering_works(): void
    {
        $this->makeIssue(['title' => 'Open issue', 'status' => 'open']);
        $this->makeIssue(['title' => 'Resolved issue', 'status' => 'resolved']);

        $response = $this->actingAs($this->admin)->get('/issues?status=open');
        $issues = $response->viewData('issues');

        $this->assertCount(1, $issues);
        $this->assertSame('Open issue', $issues->first()->title);
    }

    public function test_department_filtering_works(): void
    {
        $this->makeIssue(['title' => 'Dept A issue', 'department_id' => $this->deptA->id]);
        $this->makeIssue(['title' => 'Dept B issue', 'department_id' => $this->deptB->id]);

        $response = $this->actingAs($this->admin)->get("/issues?department_id={$this->deptA->id}");
        $issues = $response->viewData('issues');

        $this->assertCount(1, $issues);
        $this->assertSame('Dept A issue', $issues->first()->title);
    }

    public function test_area_filtering_works(): void
    {
        $this->makeIssue(['title' => 'Area A issue', 'area_id' => $this->areaA->id]);
        $this->makeIssue(['title' => 'Area B issue', 'area_id' => $this->areaB->id]);

        $response = $this->actingAs($this->admin)->get("/issues?area_id={$this->areaB->id}");
        $issues = $response->viewData('issues');

        $this->assertCount(1, $issues);
        $this->assertSame('Area B issue', $issues->first()->title);
    }

    public function test_search_filtering_works(): void
    {
        $this->makeIssue(['title' => 'Spindle bearing damaged']);
        $this->makeIssue(['title' => 'Packing machine down']);

        $response = $this->actingAs($this->admin)->get('/issues?search=bearing');
        $issues = $response->viewData('issues');

        $this->assertCount(1, $issues);
        $this->assertSame('Spindle bearing damaged', $issues->first()->title);
    }

    public function test_metrics_are_correct(): void
    {
        $this->makeIssue(['title' => 'Open', 'status' => 'open']);
        $this->makeIssue(['title' => 'Resolved', 'status' => 'resolved']);
        $this->makeIssue(['title' => 'Closed', 'status' => 'closed']);

        $response = $this->actingAs($this->admin)->get('/issues');
        $summary = $response->viewData('summary');

        $this->assertSame(3, $summary['total']);
        $this->assertSame(1, $summary['open']);
        $this->assertSame(1, $summary['resolved']);
        $this->assertSame(1, $summary['closed']);
    }

    public function test_source_daily_report_relationship_is_loaded(): void
    {
        $report = DailyReport::create([
            'report_date' => '2026-08-14',
            'reported_by' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->makeIssue([
            'title' => 'Traced issue',
            'source_daily_report_id' => $report->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/issues');
        $issues = $response->viewData('issues');

        $issue = $issues->first();
        $this->assertTrue($issue->relationLoaded('sourceDailyReport'));
        $this->assertNotNull($issue->sourceDailyReport);
        $this->assertSame($report->id, $issue->sourceDailyReport->id);
    }

    public function test_existing_routes_remain_functional(): void
    {
        $this->actingAs($this->admin)->get('/today')->assertStatus(200);
        $this->actingAs($this->admin)->get('/this-week')->assertStatus(200);
        $this->actingAs($this->admin)->get('/issues')->assertStatus(200);
    }
}
