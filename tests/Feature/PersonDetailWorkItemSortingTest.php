<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonDetailWorkItemSortingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $ulil;
    protected Department $deptAlu;
    protected Area $areaAlu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deptAlu = Department::create(['name' => 'ALUMINIUM', 'code' => 'ALU', 'is_active' => true]);
        $this->areaAlu = Area::create(['name' => 'ALUMINIUM', 'code' => 'ALU-01', 'department_id' => $this->deptAlu->id, 'is_active' => true]);

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'department_id' => $this->deptAlu->id,
            'is_active' => true,
        ]);

        $this->ulil = User::create([
            'name' => 'ULIL',
            'email' => 'ulil@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptAlu->id,
            'is_active' => true,
        ]);

        AreaAssignment::create([
            'user_id' => $this->ulil->id,
            'area_id' => $this->areaAlu->id,
            'role' => 'spv',
            'started_at' => '2026-01-01',
        ]);
    }

    /**
     * Test 1: ULIL Acceptance Case — 3-tier grouping & latest date first.
     */
    public function test_ulil_acceptance_case_grouping_and_latest_date_sorting(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00'); // Saturday 29 Aug 2026

        // 14 Completed Items (created with various past deadlines)
        $completedDates = [
            '2026-08-19', '2026-08-19', '2026-08-19',
            '2026-08-20', '2026-08-20',
            '2026-08-21',
            '2026-08-22', '2026-08-22',
            '2026-08-24',
            '2026-08-25', '2026-08-25', '2026-08-25',
            '2026-08-26',
            '2026-08-27',
        ];
        foreach ($completedDates as $idx => $date) {
            WorkItem::create([
                'title' => "Completed Task {$idx} ({$date})",
                'owner_id' => $this->ulil->id,
                'department_id' => $this->deptAlu->id,
                'area_id' => $this->areaAlu->id,
                'original_start_date' => $date,
                'original_end_date' => $date,
                'planned_start_date' => $date,
                'planned_end_date' => $date,
                'status' => 'completed',
                'completed_at' => "{$date} 15:00:00",
                'created_by' => $this->admin->id,
                'updated_by' => $this->admin->id,
            ]);
        }

        // 2 Overdue Open Items (deadlines: 2026-08-25 and 2026-08-26 -> on Sat 29 Aug, threshold is 26 Aug)
        $overdueItem1 = WorkItem::create([
            'title' => 'Overdue Task 25 Aug',
            'owner_id' => $this->ulil->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-24',
            'original_end_date' => '2026-08-25',
            'planned_start_date' => '2026-08-24',
            'planned_end_date' => '2026-08-25',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $overdueItem2 = WorkItem::create([
            'title' => 'Overdue Task 26 Aug',
            'owner_id' => $this->ulil->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-24',
            'original_end_date' => '2026-08-26',
            'planned_start_date' => '2026-08-24',
            'planned_end_date' => '2026-08-26',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // 3 Normal Open Items (deadlines: 28 Aug, 29 Aug, 29 Aug -> within 2 working-day grace period)
        $normalItem1 = WorkItem::create([
            'title' => 'Normal Open 28 Aug',
            'owner_id' => $this->ulil->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-24',
            'original_end_date' => '2026-08-28',
            'planned_start_date' => '2026-08-24',
            'planned_end_date' => '2026-08-28',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $normalItem2 = WorkItem::create([
            'title' => 'Normal Open 29 Aug A',
            'owner_id' => $this->ulil->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-29',
            'original_end_date' => '2026-08-29',
            'planned_start_date' => '2026-08-29',
            'planned_end_date' => '2026-08-29',
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $normalItem3 = WorkItem::create([
            'title' => 'Normal Open 29 Aug B',
            'owner_id' => $this->ulil->id,
            'department_id' => $this->deptAlu->id,
            'area_id' => $this->areaAlu->id,
            'original_start_date' => '2026-08-29',
            'original_end_date' => '2026-08-29',
            'planned_start_date' => '2026-08-29',
            'planned_end_date' => '2026-08-29',
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Request person detail page
        $response = $this->actingAs($this->admin)->get(route('work-items.person', ['person' => $this->ulil->id]));
        $response->assertStatus(200);

        // Verify summary metrics
        $summary = $response->viewData('summary');
        $this->assertEquals(5, $summary['active']);
        $this->assertEquals(2, $summary['overdue']);
        $this->assertEquals(0, $summary['blocked']);
        $this->assertEquals(14, $summary['completed']);

        // Verify workItems collection count = 19 (all items preserved!)
        $items = $response->viewData('workItems');
        $this->assertCount(19, $items);

        // Group 1 (Top 2 items): Overdue items (26 Aug before 25 Aug)
        $this->assertEquals($overdueItem2->id, $items[0]->id); // 26 Aug
        $this->assertEquals($overdueItem1->id, $items[1]->id); // 25 Aug

        // Group 2 (Items 2, 3, 4): Normal Open items (29 Aug before 28 Aug)
        $this->assertEquals('2026-08-29', $items[2]->planned_end_date->toDateString());
        $this->assertEquals('2026-08-29', $items[3]->planned_end_date->toDateString());
        $this->assertEquals($normalItem1->id, $items[4]->id); // 28 Aug

        // Group 3 (Items 5 to 18): Completed items (27 Aug down to 19 Aug)
        $this->assertEquals('2026-08-27', $items[5]->planned_end_date->toDateString());
        $this->assertEquals('2026-08-26', $items[6]->planned_end_date->toDateString());
        $this->assertEquals('2026-08-19', $items[18]->planned_end_date->toDateString());
    }

    /**
     * Test 2: Tab 'active' only returns overdue + normal open (2 items), with overdue first.
     */
    public function test_active_tab_only_returns_open_items_with_overdue_first(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00');

        WorkItem::create([
            'title' => 'Completed Task',
            'owner_id' => $this->ulil->id,
            'original_start_date' => '2026-08-20',
            'original_end_date' => '2026-08-20',
            'planned_start_date' => '2026-08-20',
            'planned_end_date' => '2026-08-20',
            'status' => 'completed',
            'completed_at' => '2026-08-20 15:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $overdue = WorkItem::create([
            'title' => 'Overdue Task',
            'owner_id' => $this->ulil->id,
            'original_start_date' => '2026-08-24',
            'original_end_date' => '2026-08-25',
            'planned_start_date' => '2026-08-24',
            'planned_end_date' => '2026-08-25',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        $normal = WorkItem::create([
            'title' => 'Normal Task',
            'owner_id' => $this->ulil->id,
            'original_start_date' => '2026-08-29',
            'original_end_date' => '2026-08-29',
            'planned_start_date' => '2026-08-29',
            'planned_end_date' => '2026-08-29',
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('work-items.person', [
            'person' => $this->ulil->id,
            'tab' => 'active',
        ]));
        $response->assertStatus(200);

        $items = $response->viewData('workItems');
        $this->assertCount(2, $items);
        $this->assertEquals($overdue->id, $items[0]->id);
        $this->assertEquals($normal->id, $items[1]->id);
    }

    /**
     * Test 3: Tab 'completed' only returns completed items sorted latest first.
     */
    public function test_completed_tab_only_returns_completed_items_sorted_desc(): void
    {
        WorkItem::create([
            'title' => 'Completed Task 1',
            'owner_id' => $this->ulil->id,
            'original_start_date' => '2026-08-20',
            'original_end_date' => '2026-08-20',
            'planned_start_date' => '2026-08-20',
            'planned_end_date' => '2026-08-20',
            'status' => 'completed',
            'completed_at' => '2026-08-20 15:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        WorkItem::create([
            'title' => 'Completed Task 2',
            'owner_id' => $this->ulil->id,
            'original_start_date' => '2026-08-27',
            'original_end_date' => '2026-08-27',
            'planned_start_date' => '2026-08-27',
            'planned_end_date' => '2026-08-27',
            'status' => 'completed',
            'completed_at' => '2026-08-27 15:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);
        WorkItem::create([
            'title' => 'Open Task',
            'owner_id' => $this->ulil->id,
            'original_start_date' => '2026-08-29',
            'original_end_date' => '2026-08-29',
            'planned_start_date' => '2026-08-29',
            'planned_end_date' => '2026-08-29',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('work-items.person', [
            'person' => $this->ulil->id,
            'tab' => 'completed',
        ]));
        $response->assertStatus(200);

        $items = $response->viewData('workItems');
        $this->assertCount(2, $items);
        $this->assertEquals('2026-08-27', $items[0]->planned_end_date->toDateString());
        $this->assertEquals('2026-08-20', $items[1]->planned_end_date->toDateString());
    }
}
