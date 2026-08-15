<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthUserSeeder extends Seeder
{
    public function run(): void
    {
        $ppic = Department::where('code', 'PPIC')->first();
        $qaqc = Department::where('code', 'QA-QC')->first();
        $prdFl = Department::where('code', 'PRD-FL')->first();

        // 1. Admin (PPIC)
        User::updateOrCreate(
            ['email' => 'adminppic@peroniks.com'],
            [
                'name' => 'Admin PPIC',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department_id' => $ppic?->id,
                'department_name' => $ppic?->name,
            ]
        );

        // 2. Management Representative (Manager)
        User::updateOrCreate(
            ['email' => 'mr@peroniks.com'],
            [
                'name' => 'MR Manager',
                'password' => Hash::make('password123'),
                'role' => 'manager',
                'department_id' => $qaqc?->id,
                'department_name' => $qaqc?->name,
            ]
        );

        // 3. Director
        User::updateOrCreate(
            ['email' => 'direktur@peroniks.com'],
            [
                'name' => 'Direktur Utama',
                'password' => Hash::make('peronijayajaya123'),
                'role' => 'director',
                'department_id' => null,
                'department_name' => null,
            ]
        );

        // 4. System Admin (kaizen)
        User::updateOrCreate(
            ['email' => 'admin@kaizen.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'department_id' => null,
                'department_name' => null,
            ]
        );

        // 5. Legacy Supervisor A
        User::updateOrCreate(
            ['email' => 'spv_a@kaizen.com'],
            [
                'name' => 'Supervisor A',
                'password' => Hash::make('password'),
                'role' => 'spv',
                'department_id' => $prdFl?->id,
                'department_name' => $prdFl?->name,
            ]
        );
    }
}
