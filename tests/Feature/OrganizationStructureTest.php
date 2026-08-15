<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_has_many_users(): void
    {
        $department = Department::create(['name' => 'Production', 'code' => 'PRD']);
        $userA = User::factory()->create(['role' => 'spv', 'department_id' => $department->id]);
        $userB = User::factory()->create(['role' => 'spv', 'department_id' => $department->id]);

        $this->assertCount(2, $department->users);
        $this->assertTrue($department->users->contains($userA));
        $this->assertTrue($department->users->contains($userB));
    }

    public function test_user_belongs_to_department(): void
    {
        $department = Department::create(['name' => 'QC', 'code' => 'QC']);
        $user = User::factory()->create(['role' => 'spv', 'department_id' => $department->id]);

        $this->assertTrue($user->department->is($department));
    }

    public function test_user_manager_and_subordinates(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $subordinate = User::factory()->create(['role' => 'spv', 'manager_id' => $manager->id]);

        $this->assertTrue($subordinate->manager->is($manager));
        $this->assertTrue($manager->subordinates->contains($subordinate));
    }
}
