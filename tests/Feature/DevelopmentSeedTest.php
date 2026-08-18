<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevelopmentSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_9_departments_exist(): void
    {
        $this->seed();

        $this->assertSame(9, Department::count());

        foreach (['PPIC', 'HRD', 'PRD-FL', 'PRD-FIT', 'UMUM', 'MTC', 'QA-QC', 'CNC', 'AL'] as $code) {
            $this->assertNotNull(Department::where('code', $code)->first(), "Missing department {$code}");
        }

        // Old development structure should be gone.
        foreach (['ACC & FIN', 'BBT-FL', 'DIR', 'MR', 'TAX'] as $legacyCode) {
            $this->assertNull(Department::where('code', $legacyCode)->first(), "Legacy department {$legacyCode} should be removed");
        }
    }

    public function test_all_35_areas_exist(): void
    {
        $this->seed();

        $this->assertSame(35, Area::count());
    }

    public function test_department_codes_are_unique(): void
    {
        $this->seed();

        $codes = Department::pluck('code');

        $this->assertSame($codes->count(), $codes->unique()->count());
    }

    public function test_real_personnel_have_valid_department_id(): void
    {
        $this->seed();

        $realPersonnel = User::whereNotIn('email', [
            'adminppic@peroniks.com',
            'mr@peroniks.com',
            'direktur@peroniks.com',
            'admin@kaizen.com',
            'spv_a@kaizen.com',
        ])->get();

        $this->assertSame(41, $realPersonnel->count());

        foreach ($realPersonnel as $user) {
            $this->assertNotNull($user->department_id, "{$user->email} has no department");
            $this->assertTrue(Department::whereKey($user->department_id)->exists(), "{$user->email} has invalid department");
        }
    }

    public function test_real_personnel_roles_are_valid(): void
    {
        $this->seed();

        $realPersonnel = User::whereNotIn('email', [
            'adminppic@peroniks.com',
            'mr@peroniks.com',
            'direktur@peroniks.com',
            'admin@kaizen.com',
            'spv_a@kaizen.com',
        ])->get();

        foreach ($realPersonnel as $user) {
            $this->assertContains($user->role, ['spv', 'kabag', 'manager']);
        }
    }

    public function test_director_exists_exactly_once(): void
    {
        $this->seed();

        $this->assertSame(1, User::where('role', 'director')->count());

        $director = User::where('role', 'director')->first();
        $this->assertSame('direktur@peroniks.com', $director->email);
        $this->assertNull($director->department_id);
    }

    public function test_existing_admin_users_still_exist(): void
    {
        $this->seed();

        $this->assertNotNull(User::where('email', 'adminppic@peroniks.com')->first());
        $this->assertNotNull(User::where('email', 'admin@kaizen.com')->first());
        $this->assertNotNull(User::where('email', 'mr@peroniks.com')->first());
        $this->assertNotNull(User::where('email', 'spv_a@kaizen.com')->first());
    }

    public function test_manager_hierarchy_has_no_cycles(): void
    {
        $this->seed();

        $users = User::all()->keyBy('id');

        foreach ($users as $user) {
            $seen = [];
            $current = $user;

            while ($current->manager_id !== null) {
                $this->assertArrayNotHasKey($current->manager_id, $seen, "Cycle detected via user {$user->id}");
                $seen[$current->manager_id] = true;

                $this->assertTrue($users->has($current->manager_id), "User {$user->id} references missing manager");
                $current = $users->get($current->manager_id);
            }
        }
    }

    public function test_area_assignments_count(): void
    {
        $this->seed();

        $this->assertSame(42, AreaAssignment::count());

        // HUDA should have exactly 2 assignments
        $huda = User::where('email', 'huda@peroniks.com')->first();
        $this->assertNotNull($huda);
        $this->assertSame(2, $huda->areaAssignments()->count());

        // Shift pairs should exist as separate users
        $this->assertNotNull(User::where('email', 'roji@peroniks.com')->first());
        $this->assertNotNull(User::where('email', 'majid@peroniks.com')->first());
        $this->assertNotNull(User::where('email', 'sodiq@peroniks.com')->first());
        $this->assertNotNull(User::where('email', 'bambang@peroniks.com')->first());
        $this->assertNotNull(User::where('email', 'sahrul@peroniks.com')->first());
        $this->assertNotNull(User::where('email', 'bagus@peroniks.com')->first());
    }

    public function test_seeding_twice_does_not_duplicate(): void
    {
        $this->seed();

        $departmentCount = Department::count();
        $userCount = User::count();
        $areaCount = Area::count();
        $assignmentCount = AreaAssignment::count();

        $this->seed();

        $this->assertSame($departmentCount, Department::count());
        $this->assertSame($userCount, User::count());
        $this->assertSame($areaCount, Area::count());
        $this->assertSame($assignmentCount, AreaAssignment::count());
    }
}
