<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressViewOverdueAndDaysDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $spv;
    protected Department $deptAlu;
    protected Area $areaAlu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptAlu = Department::create(['name' => 'ALUMINIUM', 'code' => 'ALU', 'is_active' => true]);
        $this->areaAlu = Area::create(['name' => 'ALUMINIUM', 'code' => 'ALU-01', 'department_id' => $this->deptAlu->id, 'is_active' => true]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'department_id' => $this->deptAlu->id,
            'is_active' => true,
        ]);

        $this->spv = User::create([
            'name' => 'ULIL',
            'email' => 'ulil@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptAlu->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: ULIL Concrete Case on 29 Aug 2026:
     * Start 28 Aug, Deadline 28 Aug, Status Berjalan.
     * Evaluated on Sat 29 Aug -> NOT overdue, Days Active = "2 Hari".
     */
    public function test_ulil_acceptance_case_not_overdue_and_running_days_display(): void
    {
        Carbon::setTestNow('2026-08-29 10:30:00'); // Saturday 29 Aug 2026

        $item = WorkItem::create([
            'title' => '3 SHIFT COR DN 80 ISO E01, DN 65 ISO E01, DN 50 ISO E01',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/progress');
        $response->assertStatus(200);

        // Verify summary overdue = 0
        $summary = $response->viewData('summary');
        $this->assertEquals(0, $summary['overdue']);
        $this->assertEquals(1, $summary['in_progress']);

        // Verify item classification is current (not overdue)
        $items = $response->viewData('groupedItems')->get($this->areaAlu->id);
        $this->assertSame('current', $items->first()->classification);

        // Verify HTML does not contain TERLAMBAT badge for this item
        $response->assertDontSee('warning</span> TERLAMBAT', false);

        // Verify HTML displays "2 Hari" (and does NOT contain fractional floating point like "2.112096402963 d")
        $response->assertSee('2 <span class="text-[9px] font-medium text-slate-400">Hari</span>', false);
        $response->assertDontSee('2.112096402963');
        $response->assertDontSee('<span class="text-[9px] font-medium text-slate-400">d</span>', false);
    }

    /**
     * Test 2: Overdue Timeline for Friday Deadline (28 Aug).
     * Saturday (Grace 1) -> NOT overdue
     * Sunday (Non-working) -> NOT overdue
     * Monday (Grace 2) -> NOT overdue
     * Tuesday 1 Sep -> OVERDUE
     */
    public function test_friday_deadline_overdue_timeline(): void
    {
        $item = WorkItem::create([
            'title' => 'Friday Task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Sat 29 Aug -> NOT overdue
        Carbon::setTestNow('2026-08-29 10:00:00');
        $resSat = $this->actingAs($this->admin)->get('/progress');
        $this->assertEquals(0, $resSat->viewData('summary')['overdue']);
        $this->assertSame('current', $resSat->viewData('groupedItems')->get($this->areaAlu->id)->first()->classification);

        // Sun 30 Aug -> NOT overdue
        Carbon::setTestNow('2026-08-30 10:00:00');
        $resSun = $this->actingAs($this->admin)->get('/progress');
        $this->assertEquals(0, $resSun->viewData('summary')['overdue']);
        $this->assertSame('current', $resSun->viewData('groupedItems')->get($this->areaAlu->id)->first()->classification);

        // Mon 31 Aug -> NOT overdue
        Carbon::setTestNow('2026-08-31 10:00:00');
        $resMon = $this->actingAs($this->admin)->get('/progress');
        $this->assertEquals(0, $resMon->viewData('summary')['overdue']);
        $this->assertSame('current', $resMon->viewData('groupedItems')->get($this->areaAlu->id)->first()->classification);

        // Tue 1 Sep -> OVERDUE
        Carbon::setTestNow('2026-09-01 10:00:00');
        $resTue = $this->actingAs($this->admin)->get('/progress');
        $this->assertEquals(1, $resTue->viewData('summary')['overdue']);
        $this->assertSame('overdue', $resTue->viewData('groupedItems')->get($this->areaAlu->id)->first()->classification);
        $resTue->assertSee('TERLAMBAT');
    }

    /**
     * Test 3: Saturday Deadline (29 Aug) Timeline.
     * Monday 31 Aug (Grace 1) -> NOT overdue
     * Tuesday 1 Sep (Grace 2) -> NOT overdue
     * Wednesday 2 Sep -> OVERDUE
     */
    public function test_saturday_deadline_overdue_timeline(): void
    {
        $item = WorkItem::create([
            'title' => 'Saturday Task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-29',
            'original_end_date' => '2026-08-29',
            'planned_start_date' => '2026-08-29',
            'planned_end_date' => '2026-08-29',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Mon 31 Aug -> NOT overdue
        Carbon::setTestNow('2026-08-31 10:00:00');
        $resMon = $this->actingAs($this->admin)->get('/progress');
        $this->assertEquals(0, $resMon->viewData('summary')['overdue']);
        $this->assertSame('current', $resMon->viewData('groupedItems')->get($this->areaAlu->id)->first()->classification);

        // Tue 1 Sep -> NOT overdue
        Carbon::setTestNow('2026-09-01 10:00:00');
        $resTue = $this->actingAs($this->admin)->get('/progress');
        $this->assertEquals(0, $resTue->viewData('summary')['overdue']);
        $this->assertSame('current', $resTue->viewData('groupedItems')->get($this->areaAlu->id)->first()->classification);

        // Wed 2 Sep -> OVERDUE
        Carbon::setTestNow('2026-09-02 10:00:00');
        $resWed = $this->actingAs($this->admin)->get('/progress');
        $this->assertEquals(1, $resWed->viewData('summary')['overdue']);
        $this->assertSame('overdue', $resWed->viewData('groupedItems')->get($this->areaAlu->id)->first()->classification);
    }

    /**
     * Test 4: Cross-Module Consistency: Dashboard == Today == Progress.
     */
    public function test_cross_module_consistency(): void
    {
        WorkItem::create([
            'title' => 'Consistency Task',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // On 29 Aug 2026:
        Carbon::setTestNow('2026-08-29 10:00:00');
        $dashSat = $this->actingAs($this->admin)->get('/dashboard?date=2026-08-29');
        $todaySat = $this->actingAs($this->admin)->get('/today?date=2026-08-29');
        $progSat = $this->actingAs($this->admin)->get('/progress');

        $this->assertEquals(0, $dashSat->viewData('overdueCount'));
        $this->assertEquals(0, $todaySat->viewData('summary')['overdue']);
        $this->assertEquals(0, $progSat->viewData('summary')['overdue']);

        // On 1 Sep 2026:
        Carbon::setTestNow('2026-09-01 10:00:00');
        $dashTue = $this->actingAs($this->admin)->get('/dashboard?date=2026-09-01');
        $todayTue = $this->actingAs($this->admin)->get('/today?date=2026-09-01');
        $progTue = $this->actingAs($this->admin)->get('/progress');

        $this->assertEquals(1, $dashTue->viewData('overdueCount'));
        $this->assertEquals(1, $todayTue->viewData('summary')['overdue']);
        $this->assertEquals(1, $progTue->viewData('summary')['overdue']);
    }
}
