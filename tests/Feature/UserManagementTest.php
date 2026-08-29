<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\User;
use App\Models\WeeklyPlan;
use App\Models\WorkItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Department $department;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'name' => 'HRD',
            'code' => 'HRD',
            'is_active' => true,
        ]);

        $this->area = Area::create([
            'name' => 'Area HR',
            'code' => 'HR-01',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Create user. Expected: is_active = true
     */
    public function test_1_create_user_defaults_to_active(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Nisa Sabrina',
            'email' => 'nisa@test.com',
            'password' => 'password123',
            'role' => 'spv',
            'department_id' => $this->department->id,
            'area_id' => $this->area->id,
            'position' => 'spv',
        ]);

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'nisa@test.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->deactivated_at);
        $this->assertNull($user->inactive_effective_date);

        $assignment = AreaAssignment::where('user_id', $user->id)->first();
        $this->assertNotNull($assignment);
        $this->assertEquals($this->area->id, $assignment->area_id);
        $this->assertNull($assignment->ended_at);
    }

    /**
     * Test 2: Deactivate user. Expected: is_active = false
     */
    public function test_2_deactivate_user_sets_inactive(): void
    {
        $user = User::create([
            'name' => 'Nisa Sabrina',
            'email' => 'nisa@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('users.deactivate', $user), [
            'effective_date' => '2026-08-29',
            'reason' => 'Resign',
            'note' => 'Pindah domisili',
        ]);

        $response->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->deactivated_at);
        $this->assertEquals('2026-08-29', $user->inactive_effective_date->toDateString());
        $this->assertEquals('Resign', $user->deactivation_reason);
        $this->assertEquals('Pindah domisili', $user->deactivation_note);
    }

    /**
     * Test 3: Deactivate user dengan effective date tertentu. Expected: AreaAssignment ended_at menggunakan effective date.
     */
    public function test_3_deactivate_user_closes_area_assignments_with_effective_date(): void
    {
        $user = User::create([
            'name' => 'Nisa Sabrina',
            'email' => 'nisa@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => true,
        ]);

        $assignment = AreaAssignment::create([
            'user_id' => $user->id,
            'area_id' => $this->area->id,
            'role' => 'spv',
            'started_at' => '2026-01-01',
            'ended_at' => null,
        ]);

        $this->actingAs($this->admin)->post(route('users.deactivate', $user), [
            'effective_date' => '2026-08-29',
            'reason' => 'Resign',
        ]);

        $assignment->refresh();
        $this->assertNotNull($assignment->ended_at);
        $this->assertEquals('2026-08-29', $assignment->ended_at->toDateString());
    }

    /**
     * Test 4: Deactivate user tidak mengubah WorkItem.owner_id.
     */
    public function test_4_deactivate_user_does_not_modify_work_item_owner(): void
    {
        $user = User::create([
            'name' => 'Nisa Sabrina',
            'email' => 'nisa@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => true,
        ]);

        $workItem = WorkItem::create([
            'title' => 'Tugas Perekrutan',
            'owner_id' => $user->id,
            'department_id' => $this->department->id,
            'area_id' => $this->area->id,
            'original_start_date' => '2026-08-01',
            'original_end_date' => '2026-08-10',
            'planned_start_date' => '2026-08-01',
            'planned_end_date' => '2026-08-10',
            'status' => 'completed',
            'completed_at' => '2026-08-10 12:00:00',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $user->deactivate(Carbon::parse('2026-08-29'), 'Resign');

        $workItem->refresh();
        $this->assertEquals($user->id, $workItem->owner_id);
        $this->assertEquals('Nisa Sabrina', $workItem->owner->name);
    }

    /**
     * Test 5: Deactivate user tidak mengubah DailyReport.reported_by.
     */
    public function test_5_deactivate_user_does_not_modify_daily_report_reporter(): void
    {
        $user = User::create([
            'name' => 'Nisa Sabrina',
            'email' => 'nisa@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => true,
        ]);

        $report = DailyReport::create([
            'report_date' => '2026-08-15',
            'reported_by' => $user->id,
            'area_id' => $this->area->id,
            'department_id' => $this->department->id,
            'today_result' => 'Laporan rekrutmen lancar',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $user->deactivate(Carbon::parse('2026-08-29'), 'Resign');

        $report->refresh();
        $this->assertEquals($user->id, $report->reported_by);
        $this->assertEquals('Nisa Sabrina', $report->reporter->name);
    }

    /**
     * Test 6: Deactivate user tidak mengubah WeeklyPlan.user_id.
     */
    public function test_6_deactivate_user_does_not_modify_weekly_plan_user_id(): void
    {
        $user = User::create([
            'name' => 'Nisa Sabrina',
            'email' => 'nisa@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => true,
        ]);

        $plan = WeeklyPlan::create([
            'user_id' => $user->id,
            'title' => 'Optimalisasi Onboarding',
            'expected_output' => 'Waktu orientasi turun 20%',
            'category' => 'improvement',
            'impact_level' => 'medium',
            'week_start_date' => '2026-08-10',
            'week_end_date' => '2026-08-16',
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $user->deactivate(Carbon::parse('2026-08-29'), 'Resign');

        $plan->refresh();
        $this->assertEquals($user->id, $plan->user_id);
        $this->assertEquals('Nisa Sabrina', $plan->user->name);
    }

    /**
     * Test 7: Inactive user tidak muncul pada dropdown input DailyReport baru.
     */
    public function test_7_inactive_user_does_not_appear_in_new_daily_report_options(): void
    {
        $activeUser = User::create([
            'name' => 'Bagus Aktif',
            'email' => 'bagus@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => true,
        ]);
        AreaAssignment::create([
            'user_id' => $activeUser->id,
            'area_id' => $this->area->id,
            'role' => 'spv',
            'started_at' => '2026-01-01',
        ]);

        $inactiveUser = User::create([
            'name' => 'Nisa Inaktif',
            'email' => 'nisa_inactive@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => false,
            'inactive_effective_date' => '2026-08-29',
        ]);
        AreaAssignment::create([
            'user_id' => $inactiveUser->id,
            'area_id' => $this->area->id,
            'role' => 'spv',
            'started_at' => '2026-01-01',
            'ended_at' => '2026-08-29',
        ]);

        $response = $this->actingAs($this->admin)->get(route('daily-reports.create'));
        $response->assertStatus(200);
        $response->assertSee('Bagus Aktif');
        $response->assertDontSee('Nisa Inaktif');
    }

    /**
     * Test 8: Historical DailyReport tetap dapat menemukan inactive user.
     */
    public function test_8_historical_daily_report_finds_inactive_user(): void
    {
        $inactiveUser = User::create([
            'name' => 'Nisa Sabrina',
            'email' => 'nisa@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => false,
            'inactive_effective_date' => '2026-08-29',
        ]);

        $report = DailyReport::create([
            'report_date' => '2026-08-15',
            'reported_by' => $inactiveUser->id,
            'area_id' => $this->area->id,
            'department_id' => $this->department->id,
            'today_result' => 'Historical result notes',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('daily-reports.edit', $report));
        $response->assertStatus(200);
        $response->assertSee('Nisa Sabrina');
        $response->assertSee('Historical result notes');
    }

    /**
     * Test 9: Historical compliance denominator tidak berubah ketika user resign setelah tanggal historical.
     */
    public function test_9_historical_compliance_denominator_remains_correct(): void
    {
        // User A was active all month
        $userA = User::create([
            'name' => 'User A',
            'email' => 'usera@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => true,
        ]);
        AreaAssignment::create([
            'user_id' => $userA->id,
            'area_id' => $this->area->id,
            'role' => 'spv',
            'started_at' => '2026-08-01',
            'ended_at' => null,
        ]);

        // User Nisa resigned on 2026-08-26 (Wednesday)
        $nisa = User::create([
            'name' => 'Nisa',
            'email' => 'nisa@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => true,
        ]);
        $nisaAssignment = AreaAssignment::create([
            'user_id' => $nisa->id,
            'area_id' => $this->area->id,
            'role' => 'spv',
            'started_at' => '2026-08-01',
            'ended_at' => null,
        ]);

        // Admin deactivates Nisa on 2026-08-31 with effective date 2026-08-26
        $nisa->deactivate(Carbon::parse('2026-08-26'), 'Resign');

        // Check dashboard for week of 24 Aug 2026 - 30 Aug 2026
        $response = $this->actingAs($this->admin)->get(route('dashboard.index', ['date' => '2026-08-25']));
        $response->assertStatus(200);

        $days = $response->viewData('days');
        $this->assertIsArray($days);

        // On Monday (2026-08-24), both User A and Nisa were active -> total eligible = 2
        $mon = collect($days)->firstWhere('dateStr', '2026-08-24');
        $this->assertNotNull($mon);
        $this->assertEquals(2, $mon['total']);

        // On Tuesday (2026-08-25), both User A and Nisa were active -> total eligible = 2
        $tue = collect($days)->firstWhere('dateStr', '2026-08-25');
        $this->assertNotNull($tue);
        $this->assertEquals(2, $tue['total']);

        // On Thursday (2026-08-27), Nisa was inactive -> total eligible = 1
        $thu = collect($days)->firstWhere('dateStr', '2026-08-27');
        $this->assertNotNull($thu);
        $this->assertEquals(1, $thu['total']);
    }

    /**
     * Test 10: Create replacement user menghasilkan User ID baru.
     */
    public function test_10_create_replacement_user_creates_new_user_id(): void
    {
        $nisa = User::create([
            'name' => 'Nisa Sabrina',
            'email' => 'nisa@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => false,
            'inactive_effective_date' => '2026-08-29',
        ]);

        $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Dewi Sartika',
            'email' => 'dewi@test.com',
            'password' => 'password123',
            'role' => 'spv',
            'department_id' => $this->department->id,
            'area_id' => $this->area->id,
            'position' => 'spv',
        ]);

        $dewi = User::where('email', 'dewi@test.com')->first();
        $this->assertNotNull($dewi);
        $this->assertNotEquals($nisa->id, $dewi->id);
        $this->assertTrue($dewi->is_active);
    }

    /**
     * Test 11: Replacement user tidak memiliki historical records user lama.
     */
    public function test_11_replacement_user_does_not_inherit_old_user_records(): void
    {
        $nisa = User::create([
            'name' => 'Nisa Sabrina',
            'email' => 'nisa@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => false,
            'inactive_effective_date' => '2026-08-29',
        ]);

        WorkItem::create([
            'title' => 'Tugas Lama Nisa',
            'owner_id' => $nisa->id,
            'department_id' => $this->department->id,
            'area_id' => $this->area->id,
            'original_start_date' => '2026-08-01',
            'original_end_date' => '2026-08-10',
            'planned_start_date' => '2026-08-01',
            'planned_end_date' => '2026-08-10',
            'status' => 'completed',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $dewi = User::create([
            'name' => 'Dewi Sartika',
            'email' => 'dewi@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => true,
        ]);

        $this->assertEquals(1, $nisa->ownedWorkItems()->count());
        $this->assertEquals(0, $dewi->ownedWorkItems()->count());
    }

    /**
     * Test 12: User inactive yang memiliki remaining WorkItems tetap memiliki WorkItems tersebut.
     */
    public function test_12_inactive_user_with_remaining_work_items_retains_them(): void
    {
        $nisa = User::create([
            'name' => 'Nisa Sabrina',
            'email' => 'nisa@test.com',
            'password' => bcrypt('password'),
            'role' => 'spv',
            'is_active' => true,
        ]);

        $openItem1 = WorkItem::create([
            'title' => 'Tugas Belum Selesai 1',
            'owner_id' => $nisa->id,
            'department_id' => $this->department->id,
            'area_id' => $this->area->id,
            'original_start_date' => '2026-08-20',
            'original_end_date' => '2026-08-30',
            'planned_start_date' => '2026-08-20',
            'planned_end_date' => '2026-08-30',
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $openItem2 = WorkItem::create([
            'title' => 'Tugas Belum Selesai 2',
            'owner_id' => $nisa->id,
            'department_id' => $this->department->id,
            'area_id' => $this->area->id,
            'original_start_date' => '2026-08-22',
            'original_end_date' => '2026-08-31',
            'planned_start_date' => '2026-08-22',
            'planned_end_date' => '2026-08-31',
            'status' => 'not_started',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        // Deactivate Nisa
        $nisa->deactivate(Carbon::parse('2026-08-29'), 'Resign');

        $this->assertFalse($nisa->is_active);

        // Assert items are untouched and still owned by Nisa
        $this->assertEquals(2, $nisa->ownedWorkItems()->whereIn('status', ['not_started', 'in_progress', 'blocked'])->count());
        $this->assertEquals($nisa->id, $openItem1->fresh()->owner_id);
        $this->assertEquals($nisa->id, $openItem2->fresh()->owner_id);
    }
}
