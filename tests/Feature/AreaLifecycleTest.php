<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makeArea(?int $departmentId = null): Area
    {
        return Area::create([
            'code' => 'COR-FL',
            'name' => 'Cor Flange',
            'department_id' => $departmentId,
        ]);
    }

    public function test_area_code_is_immutable(): void
    {
        $area = $this->makeArea();

        $this->expectException(\LogicException::class);

        $area->code = 'COR-FX';
        $area->save();
    }

    public function test_area_name_can_change(): void
    {
        $area = $this->makeArea();

        $area->name = 'Casting Flange';
        $area->save();

        $this->assertSame('Casting Flange', $area->fresh()->name);
        $this->assertSame('COR-FL', $area->fresh()->code);
    }

    public function test_area_belongs_to_department(): void
    {
        $department = Department::create(['code' => 'PRD-FL', 'name' => 'Produksi Flange']);
        $area = $this->makeArea($department->id);

        $this->assertTrue($area->department->is($department));
    }

    public function test_area_defaults_to_active(): void
    {
        $area = $this->makeArea();

        $this->assertTrue($area->is_active);
        $this->assertNull($area->deactivated_at);
    }

    public function test_area_can_deactivate(): void
    {
        $area = $this->makeArea();

        $area->deactivate();

        $this->assertFalse($area->fresh()->is_active);
        $this->assertNotNull($area->fresh()->deactivated_at);
    }

    public function test_area_can_reactivate(): void
    {
        $area = $this->makeArea();
        $area->deactivate();

        $area->reactivate();

        $this->assertTrue($area->fresh()->is_active);
        $this->assertNull($area->fresh()->deactivated_at);
    }

    public function test_inactive_area_remains_queryable(): void
    {
        $area = $this->makeArea();
        $area->deactivate();

        $this->assertTrue(Area::whereKey($area->id)->exists());
        $this->assertSame(1, Area::inactive()->count());
        $this->assertSame(0, Area::active()->count());
    }
}
