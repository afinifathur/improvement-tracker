<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makeDepartment(): Department
    {
        return Department::create(['code' => 'PRD-FL', 'name' => 'Produksi Flange']);
    }

    public function test_department_code_is_immutable(): void
    {
        $department = $this->makeDepartment();

        $this->expectException(\LogicException::class);

        $department->code = 'PRD-FX';
        $department->save();
    }

    public function test_department_name_can_change(): void
    {
        $department = $this->makeDepartment();

        $department->name = 'Flange Manufacturing';
        $department->save();

        $this->assertSame('Flange Manufacturing', $department->fresh()->name);
        $this->assertSame('PRD-FL', $department->fresh()->code);
    }

    public function test_department_defaults_to_active(): void
    {
        $department = $this->makeDepartment();

        $this->assertTrue($department->is_active);
        $this->assertNull($department->deactivated_at);
    }

    public function test_department_can_deactivate(): void
    {
        $department = $this->makeDepartment();

        $department->deactivate();

        $this->assertFalse($department->fresh()->is_active);
        $this->assertNotNull($department->fresh()->deactivated_at);
    }

    public function test_department_can_reactivate(): void
    {
        $department = $this->makeDepartment();
        $department->deactivate();

        $department->reactivate();

        $this->assertTrue($department->fresh()->is_active);
        $this->assertNull($department->fresh()->deactivated_at);
    }

    public function test_inactive_department_remains_queryable(): void
    {
        $department = $this->makeDepartment();
        $department->deactivate();

        $this->assertTrue(Department::whereKey($department->id)->exists());
        $this->assertSame(1, Department::inactive()->count());
        $this->assertSame(0, Department::active()->count());
    }
}
