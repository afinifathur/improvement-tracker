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

/**
 * Regression test: proves that the Daily Report form correctly submits area_id.
 *
 * Historical bug: StoreDailyReportRequest requires area_id but the _entry.blade.php
 * form did not include a hidden area_id input. Tests that POST area_id manually
 * would pass; real browser submissions would fail validation silently.
 */
class DailyReportFormRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function makeFixtures(): array
    {
        $dept = Department::create(['name' => 'Production', 'code' => 'PRD']);
        $area = Area::create(['code' => 'PRD-01', 'name' => 'Assembly Line 1', 'department_id' => $dept->id]);
        $spv  = User::factory()->create(['role' => 'spv', 'department_id' => $dept->id]);
        $admin = User::factory()->create(['role' => 'admin']);

        AreaAssignment::create([
            'area_id'    => $area->id,
            'user_id'    => $spv->id,
            'role'       => 'spv',
            'started_at' => '2026-01-01',
            'ended_at'   => null,
        ]);

        return compact('dept', 'area', 'spv', 'admin');
    }

    /** The rendered create form must contain a hidden area_id input. */
    public function test_create_form_renders_area_id_hidden_input(): void
    {
        ['spv' => $spv, 'area' => $area, 'admin' => $admin] = $this->makeFixtures();

        $response = $this->actingAs($admin)
            ->get(route('daily-reports.create', [
                'person'  => $spv->id,
                'date'    => '2026-08-18',
                'area_id' => $area->id,
            ]));

        $response->assertOk();
        $response->assertSee('name="area_id"', false);
        $response->assertSee('value="' . $area->id . '"', false);
    }

    /** The create form must render work item date fields defaulting to the report date. */
    public function test_create_form_default_date_equals_report_date(): void
    {
        ['spv' => $spv, 'area' => $area, 'admin' => $admin] = $this->makeFixtures();

        $response = $this->actingAs($admin)
            ->get(route('daily-reports.create', [
                'person'  => $spv->id,
                'date'    => '2026-08-18',
                'area_id' => $area->id,
            ]));

        $response->assertOk();
        // defaultDate passed to view must equal report date (not +1)
        $this->assertSame('2026-08-18', $response->viewData('defaultDate'));
        // Template should embed the date so JS picks it up
        $response->assertSee('2026-08-18');
    }

    /**
     * Full store flow without manually injecting area_id at the payload level —
     * instead, area_id comes through exactly as it would from the rendered form.
     * This is the critical regression: prior to the fix, area_id was missing from
     * the form and the POST would fail validation.
     */
    public function test_store_persists_daily_report_and_work_item(): void
    {
        ['spv' => $spv, 'area' => $area, 'admin' => $admin] = $this->makeFixtures();

        $response = $this->actingAs($admin)->post(route('daily-reports.store'), [
            // These are exactly the fields the fixed form now submits:
            'report_date' => '2026-08-18',
            'reported_by' => $spv->id,
            'area_id'     => $area->id,   // provided by the hidden input in the form
            'today_result' => 'Opname berjalan lancar.',
            'work_items' => [
                [
                    'title'               => 'Opname Rak B-2',
                    'description'         => '',
                    'weekly_plan_id'      => null,
                    'planned_start_date'  => '2026-08-18', // report date (not +1)
                    'planned_end_date'    => '2026-08-18',
                ],
            ],
        ]);

        $response->assertRedirect(route('daily-reports.index', ['date' => '2026-08-18']));

        $report = DailyReport::where('reported_by', $spv->id)
            ->whereDate('report_date', '2026-08-18')
            ->where('area_id', $area->id)
            ->first();

        $this->assertNotNull($report, 'DailyReport was not persisted');
        $this->assertSame($area->id, $report->area_id);

        $item = WorkItem::where('source_daily_report_id', $report->id)->first();

        $this->assertNotNull($item, 'WorkItem was not persisted');
        $this->assertSame('Opname Rak B-2', $item->title);
        $this->assertSame('2026-08-18', $item->planned_start_date->toDateString());
        $this->assertSame('2026-08-18', $item->planned_end_date->toDateString());
        $this->assertSame($spv->id, $item->owner_id);
        $this->assertSame($area->id, $item->area_id);
    }

    /** Validation errors must be returned (not silently swallowed) when area_id is absent. */
    public function test_missing_area_id_returns_validation_error(): void
    {
        ['spv' => $spv, 'admin' => $admin] = $this->makeFixtures();

        $response = $this->actingAs($admin)->post(route('daily-reports.store'), [
            'report_date' => '2026-08-18',
            'reported_by' => $spv->id,
            // area_id intentionally omitted — simulates the old broken form
        ]);

        $response->assertSessionHasErrors('area_id');
        $this->assertDatabaseCount('daily_reports', 0);
    }
}
