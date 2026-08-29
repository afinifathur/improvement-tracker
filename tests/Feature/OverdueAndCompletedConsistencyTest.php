<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueAndCompletedConsistencyTest extends TestCase
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
     * PART A: ULIL Concrete Case on 29 Aug 2026:
     * Deadline Friday 28 Aug, Status Berjalan.
     * Evaluated on Saturday 29 Aug -> NOT overdue, does NOT appear in /overdue.
     */
    public function test_ulil_acceptance_case_not_in_overdue_view(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00'); // Saturday 29 Aug 2026

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

        $response = $this->actingAs($this->admin)->get('/overdue');
        $response->assertStatus(200);

        // Summary total must be 0
        $this->assertEquals(0, $response->viewData('summary')['total']);

        // Grouped items must not contain ULIL's item
        $grouped = $response->viewData('groupedItems');
        $this->assertFalse($grouped->has($this->areaAlu->id));
        $response->assertDontSee($item->title);
    }

    /**
     * PART A: Friday Deadline Timeline on /overdue:
     * Saturday (Grace 1) -> NOT in /overdue
     * Sunday (Non-working) -> NOT in /overdue
     * Monday (Grace 2) -> NOT in /overdue
     * Tuesday 1 Sep -> APPEARS in /overdue
     */
    public function test_friday_deadline_overdue_view_timeline(): void
    {
        $item = WorkItem::create([
            'title' => 'Friday Item',
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

        // Sat 29 Aug -> NOT in /overdue
        Carbon::setTestNow('2026-08-29 10:00:00');
        $resSat = $this->actingAs($this->admin)->get('/overdue');
        $this->assertEquals(0, $resSat->viewData('summary')['total']);
        $this->assertFalse($resSat->viewData('groupedItems')->has($this->areaAlu->id));

        // Sun 30 Aug -> NOT in /overdue
        Carbon::setTestNow('2026-08-30 10:00:00');
        $resSun = $this->actingAs($this->admin)->get('/overdue');
        $this->assertEquals(0, $resSun->viewData('summary')['total']);

        // Mon 31 Aug -> NOT in /overdue
        Carbon::setTestNow('2026-08-31 10:00:00');
        $resMon = $this->actingAs($this->admin)->get('/overdue');
        $this->assertEquals(0, $resMon->viewData('summary')['total']);

        // Tue 1 Sep -> APPEARS in /overdue
        Carbon::setTestNow('2026-09-01 10:00:00');
        $resTue = $this->actingAs($this->admin)->get('/overdue');
        $this->assertEquals(1, $resTue->viewData('summary')['total']);
        $this->assertTrue($resTue->viewData('groupedItems')->has($this->areaAlu->id));
        $resTue->assertSee('Friday Item');
    }

    /**
     * PART A: Saturday Deadline Timeline on /overdue:
     * Monday 31 Aug (Grace 1) -> NOT in /overdue
     * Tuesday 1 Sep (Grace 2) -> NOT in /overdue
     * Wednesday 2 Sep -> APPEARS in /overdue
     */
    public function test_saturday_deadline_overdue_view_timeline(): void
    {
        $item = WorkItem::create([
            'title' => 'Saturday Item',
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

        // Mon 31 Aug -> NOT in /overdue
        Carbon::setTestNow('2026-08-31 10:00:00');
        $resMon = $this->actingAs($this->admin)->get('/overdue');
        $this->assertEquals(0, $resMon->viewData('summary')['total']);

        // Tue 1 Sep -> NOT in /overdue
        Carbon::setTestNow('2026-09-01 10:00:00');
        $resTue = $this->actingAs($this->admin)->get('/overdue');
        $this->assertEquals(0, $resTue->viewData('summary')['total']);

        // Wed 2 Sep -> APPEARS in /overdue
        Carbon::setTestNow('2026-09-02 10:00:00');
        $resWed = $this->actingAs($this->admin)->get('/overdue');
        $this->assertEquals(1, $resWed->viewData('summary')['total']);
        $this->assertTrue($resWed->viewData('groupedItems')->has($this->areaAlu->id));
        $resWed->assertSee('Saturday Item');
    }

    /**
     * PART A: Completed & Cancelled excluded, Blocked included after threshold.
     */
    public function test_completed_and_cancelled_excluded_and_blocked_included(): void
    {
        Carbon::setTestNow('2026-09-01 10:00:00'); // Tuesday

        // Completed item with Friday deadline (should NOT appear)
        WorkItem::create([
            'title' => 'Completed Friday',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'completed',
            'completed_at' => '2026-08-28 17:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Cancelled item with Friday deadline (should NOT appear)
        WorkItem::create([
            'title' => 'Cancelled Friday',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'cancelled',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Blocked item with Friday deadline (SHOULD appear)
        WorkItem::create([
            'title' => 'Blocked Friday',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'blocked',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/overdue');
        $response->assertStatus(200);

        $this->assertEquals(1, $response->viewData('summary')['total']);
        $this->assertEquals(1, $response->viewData('summary')['blocked']);
        $response->assertSee('Blocked Friday');
        $response->assertDontSee('Completed Friday');
        $response->assertDontSee('Cancelled Friday');
    }

    /**
     * PART B: Completed View Sorting newest -> oldest by completion date.
     */
    public function test_completed_view_orders_newest_to_oldest(): void
    {
        $item1 = WorkItem::create([
            'title' => 'Completed 19 Aug',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-19',
            'original_end_date' => '2026-08-19',
            'planned_start_date' => '2026-08-19',
            'planned_end_date' => '2026-08-19',
            'status' => 'completed',
            'completed_at' => '2026-08-19 15:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $item2 = WorkItem::create([
            'title' => 'Completed 27 Aug',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-27',
            'original_end_date' => '2026-08-27',
            'planned_start_date' => '2026-08-27',
            'planned_end_date' => '2026-08-27',
            'status' => 'completed',
            'completed_at' => '2026-08-27 16:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $item3 = WorkItem::create([
            'title' => 'Completed 25 Aug',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-25',
            'original_end_date' => '2026-08-25',
            'planned_start_date' => '2026-08-25',
            'planned_end_date' => '2026-08-25',
            'status' => 'completed',
            'completed_at' => '2026-08-25 11:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $item4 = WorkItem::create([
            'title' => 'Completed 29 Aug',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-29',
            'original_end_date' => '2026-08-29',
            'planned_start_date' => '2026-08-29',
            'planned_end_date' => '2026-08-29',
            'status' => 'completed',
            'completed_at' => '2026-08-29 14:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/completed');
        $response->assertStatus(200);

        $items = $response->viewData('groupedItems')->get($this->areaAlu->id)->values();

        $this->assertCount(4, $items);
        $this->assertEquals($item4->id, $items[0]->id); // 29 Aug
        $this->assertEquals($item2->id, $items[1]->id); // 27 Aug
        $this->assertEquals($item3->id, $items[2]->id); // 25 Aug
        $this->assertEquals($item1->id, $items[3]->id); // 19 Aug
    }

    /**
     * PART B: Completed View Filters preserve newest -> oldest sorting.
     */
    public function test_completed_view_filters_preserve_sorting(): void
    {
        $otherDept = Department::create(['name' => 'FOUNDRY', 'code' => 'FDY', 'is_active' => true]);
        $otherArea = Area::create(['name' => 'FOUNDRY', 'code' => 'FDY-01', 'department_id' => $otherDept->id, 'is_active' => true]);

        // Alu items
        $item1 = WorkItem::create([
            'title' => 'Alu Old',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-20',
            'original_end_date' => '2026-08-20',
            'planned_start_date' => '2026-08-20',
            'planned_end_date' => '2026-08-20',
            'status' => 'completed',
            'completed_at' => '2026-08-20 10:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $item2 = WorkItem::create([
            'title' => 'Alu New',
            'owner_id' => $this->spv->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-28',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-28',
            'planned_end_date' => '2026-08-28',
            'status' => 'completed',
            'completed_at' => '2026-08-28 10:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Filter by dept ALU
        $response = $this->actingAs($this->admin)->get('/completed?department_id=' . $this->deptAlu->id);
        $response->assertStatus(200);

        $items = $response->viewData('groupedItems')->get($this->areaAlu->id)->values();
        $this->assertCount(2, $items);
        $this->assertEquals($item2->id, $items[0]->id); // 28 Aug
        $this->assertEquals($item1->id, $items[1]->id); // 20 Aug
    }

    /**
     * Cross-Module Consistency: Dashboard == Today == Plan == Progress == Overdue.
     */
    public function test_cross_module_consistency_all_five_views(): void
    {
        WorkItem::create([
            'title' => 'Consistency Item',
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

        // On 29 Aug 2026 (Saturday - Grace Day 1):
        Carbon::setTestNow('2026-08-29 10:00:00');
        $dashSat = $this->actingAs($this->admin)->get('/dashboard?date=2026-08-29');
        $todaySat = $this->actingAs($this->admin)->get('/today?date=2026-08-29');
        $planSat = $this->actingAs($this->admin)->get('/plan');
        $progSat = $this->actingAs($this->admin)->get('/progress');
        $overSat = $this->actingAs($this->admin)->get('/overdue');

        $this->assertEquals(0, $dashSat->viewData('overdueCount'));
        $this->assertEquals(0, $todaySat->viewData('summary')['overdue']);
        $this->assertSame('current', $planSat->viewData('groupedItems')->get($this->areaAlu->id)->first()->classification);
        $this->assertEquals(0, $progSat->viewData('summary')['overdue']);
        $this->assertEquals(0, $overSat->viewData('summary')['total']);

        // On 1 Sep 2026 (Tuesday - Day +3 Working Day = OVERDUE):
        Carbon::setTestNow('2026-09-01 10:00:00');
        $dashTue = $this->actingAs($this->admin)->get('/dashboard?date=2026-09-01');
        $todayTue = $this->actingAs($this->admin)->get('/today?date=2026-09-01');
        $planTue = $this->actingAs($this->admin)->get('/plan');
        $progTue = $this->actingAs($this->admin)->get('/progress');
        $overTue = $this->actingAs($this->admin)->get('/overdue');

        $this->assertEquals(1, $dashTue->viewData('overdueCount'));
        $this->assertEquals(1, $todayTue->viewData('summary')['overdue']);
        $this->assertSame('overdue', $planTue->viewData('groupedItems')->get($this->areaAlu->id)->first()->classification);
        $this->assertEquals(1, $progTue->viewData('summary')['overdue']);
        $this->assertEquals(1, $overTue->viewData('summary')['total']);
    }
}
