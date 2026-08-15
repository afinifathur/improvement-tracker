<?php

namespace Tests\Feature;

use App\Enums\Position;
use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaViewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $deptA;

    private Department $deptB;

    private Area $areaA;

    private Area $areaB;

    private User $spvA;

    private User $spvB;

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

        $this->spvB = User::create([
            'name' => 'Supervisor B',
            'email' => 'spvb@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->deptB->id,
        ]);
    }

    private function makeWorkItem(array $overrides = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'title' => 'Sample work',
            'owner_id' => $this->spvA->id,
            'department_id' => $this->deptA->id,
            'area_id' => $this->areaA->id,
            'original_start_date' => '2026-08-10',
            'original_end_date' => '2026-08-12',
            'planned_start_date' => '2026-08-10',
            'planned_end_date' => '2026-08-12',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_area_route_requires_authentication(): void
    {
        $this->get('/area')->assertRedirect('/login');
    }

    public function test_area_route_accepts_admin_manager_director(): void
    {
        $this->actingAs($this->admin)->get('/area')->assertStatus(200);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'mgr@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
        ]);
        $this->actingAs($manager)->get('/area')->assertStatus(200);

        $director = User::create([
            'name' => 'Director User',
            'email' => 'dir@test.com',
            'password' => bcrypt('password'),
            'role' => 'director',
        ]);
        $this->actingAs($director)->get('/area')->assertStatus(200);

        $this->actingAs($this->spvA)->get('/area')->assertStatus(403);
    }

    public function test_area_overview_loads(): void
    {
        $this->makeWorkItem(['title' => 'Area A work', 'area_id' => $this->areaA->id]);

        $response = $this->actingAs($this->admin)->get('/area');
        $response->assertStatus(200);

        $areaRows = $response->viewData('areaRows');
        $this->assertNotNull($areaRows);
        $this->assertTrue($areaRows->contains(fn ($row) => $row->area && $row->area->id === $this->areaA->id));
    }

    public function test_selecting_area_filters_work_items(): void
    {
        $this->makeWorkItem(['title' => 'Area A work', 'area_id' => $this->areaA->id]);
        $this->makeWorkItem(['title' => 'Area B work', 'area_id' => $this->areaB->id]);

        $response = $this->actingAs($this->admin)->get("/area?area={$this->areaA->id}");
        $response->assertStatus(200);

        $workItems = $response->viewData('workItems');
        $this->assertCount(1, $workItems);
        $this->assertSame('Area A work', $workItems->first()->title);
    }

    public function test_area_id_filtering_is_correct(): void
    {
        $this->makeWorkItem(['title' => 'Area A work', 'area_id' => $this->areaA->id]);
        $this->makeWorkItem(['title' => 'Area B work', 'area_id' => $this->areaB->id]);

        $response = $this->actingAs($this->admin)->get("/area?area={$this->areaA->id}");

        foreach ($response->viewData('workItems') as $item) {
            $this->assertSame($this->areaA->id, $item->area_id);
        }
    }

    public function test_status_filtering_works(): void
    {
        $this->makeWorkItem(['title' => 'Blocked work', 'area_id' => $this->areaA->id, 'status' => 'blocked']);
        $this->makeWorkItem(['title' => 'Active work', 'area_id' => $this->areaA->id, 'status' => 'in_progress']);

        $response = $this->actingAs($this->admin)->get("/area?area={$this->areaA->id}&status=blocked");
        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems);
        $this->assertSame('Blocked work', $workItems->first()->title);
    }

    public function test_department_filtering_works(): void
    {
        $this->makeWorkItem(['title' => 'Dept A work', 'area_id' => $this->areaA->id, 'department_id' => $this->deptA->id]);
        $this->makeWorkItem(['title' => 'Dept B work', 'area_id' => $this->areaA->id, 'department_id' => $this->deptB->id]);

        $response = $this->actingAs($this->admin)->get("/area?area={$this->areaA->id}&department_id={$this->deptA->id}");
        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems);
        $this->assertSame('Dept A work', $workItems->first()->title);
    }

    public function test_person_filtering_works(): void
    {
        $this->makeWorkItem(['title' => 'Spv A work', 'area_id' => $this->areaA->id, 'owner_id' => $this->spvA->id]);
        $this->makeWorkItem(['title' => 'Spv B work', 'area_id' => $this->areaA->id, 'owner_id' => $this->spvB->id]);

        $response = $this->actingAs($this->admin)->get("/area?area={$this->areaA->id}&owner_id={$this->spvB->id}");
        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems);
        $this->assertSame('Spv B work', $workItems->first()->title);
    }

    public function test_search_filtering_works(): void
    {
        $this->makeWorkItem(['title' => 'Alpha work', 'area_id' => $this->areaA->id]);
        $this->makeWorkItem(['title' => 'Beta work', 'area_id' => $this->areaA->id]);

        $response = $this->actingAs($this->admin)->get("/area?area={$this->areaA->id}&search=Alpha");
        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems);
        $this->assertSame('Alpha work', $workItems->first()->title);
    }

    public function test_completed_historical_work_items_remain_visible(): void
    {
        $this->makeWorkItem([
            'title' => 'Old completed work',
            'area_id' => $this->areaA->id,
            'status' => 'completed',
            'planned_start_date' => '2026-01-05',
            'planned_end_date' => '2026-01-08',
        ]);

        $response = $this->actingAs($this->admin)->get("/area?area={$this->areaA->id}&status=completed");
        $workItems = $response->viewData('workItems');

        $this->assertCount(1, $workItems);
        $this->assertSame('Old completed work', $workItems->first()->title);
    }

    public function test_null_area_items_appear_under_unassigned_area(): void
    {
        $this->makeWorkItem(['title' => 'Unassigned work', 'area_id' => null]);

        // Overview contains an unassigned row.
        $overview = $this->actingAs($this->admin)->get('/area');
        $areaRows = $overview->viewData('areaRows');
        $unassignedRow = $areaRows->first(fn ($row) => $row->area === null);
        $this->assertNotNull($unassignedRow);
        $this->assertSame(1, $unassignedRow->counts['active']);

        // Unassigned detail lists the null-area item.
        $detail = $this->actingAs($this->admin)->get('/area?area=unassigned');
        $workItems = $detail->viewData('workItems');
        $this->assertCount(1, $workItems);
        $this->assertSame('Unassigned work', $workItems->first()->title);
    }

    public function test_responsible_person_is_displayed_when_assignment_exists(): void
    {
        AreaAssignment::create([
            'area_id' => $this->areaA->id,
            'user_id' => $this->spvA->id,
            'role' => Position::Spv,
            'started_at' => '2026-01-01',
        ]);

        $response = $this->actingAs($this->admin)->get('/area');
        $areaRows = $response->viewData('areaRows');

        $row = $areaRows->first(fn ($row) => $row->area && $row->area->id === $this->areaA->id);
        $this->assertStringContainsString('Supervisor A', $row->responsible);
    }

    public function test_historical_ownership_is_not_rewritten_by_current_assignment(): void
    {
        // Person A is CURRENTLY assigned to Area B.
        AreaAssignment::create([
            'area_id' => $this->areaB->id,
            'user_id' => $this->spvA->id,
            'role' => Position::Spv,
            'started_at' => '2026-01-01',
        ]);

        // But a historical WorkItem belongs to Area A with owner Person A.
        $this->makeWorkItem([
            'title' => 'Historical Area A work',
            'owner_id' => $this->spvA->id,
            'area_id' => $this->areaA->id,
        ]);

        // Area A still lists the work item.
        $areaAResponse = $this->actingAs($this->admin)->get("/area?area={$this->areaA->id}");
        $this->assertTrue(
            $areaAResponse->viewData('workItems')->pluck('title')->contains('Historical Area A work')
        );

        // Area B does NOT inherit the work item via the current assignment.
        $areaBResponse = $this->actingAs($this->admin)->get("/area?area={$this->areaB->id}");
        $this->assertFalse(
            $areaBResponse->viewData('workItems')->pluck('title')->contains('Historical Area A work')
        );

        // Person A still owns the work item.
        $personResponse = $this->actingAs($this->admin)->get("/person?person={$this->spvA->id}");
        $this->assertTrue(
            $personResponse->viewData('workItems')->pluck('title')->contains('Historical Area A work')
        );
    }

    public function test_sidebar_route_works(): void
    {
        $response = $this->actingAs($this->admin)->get('/area');
        $this->assertStringContainsString(route('work-items.area'), $response->getContent());
    }
}
