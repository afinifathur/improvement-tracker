<?php

namespace Database\Seeders;

use App\Enums\Position;
use App\Models\Area;
use App\Models\AreaAssignment;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private array $deptNameToCode = [
        'PPIC' => 'PPIC',
        'HRD' => 'HRD',
        'PRODUKSI FLANGE' => 'PRD-FL',
        'PRODUKSI FITTING' => 'PRD-FIT',
        'UMUM' => 'UMUM',
        'MAINTENANCE' => 'MTC',
        'QA/QC' => 'QA-QC',
        'MILLING CNC' => 'CNC',
        'ALUMINIUM' => 'AL',
    ];

    private array $areaNameToCode = [
        'PPIC' => 'PPIC',
        'IT DAN K3' => 'IT-K3',
        'CCTV' => 'CCTV',
        'HR' => 'HR',
        'BAHAN BAKU' => 'BHN-BK',
        'GUDANG FLANGE' => 'GD-FL',
        'GUDANG FITTING' => 'GD-FIT',
        'COR FLANGE' => 'COR-FL',
        'NETTO FLANGE' => 'NT-FL',
        'NETTO FITTING' => 'NT-FIT',
        'KIMIA' => 'KM',
        'CNC FITTING' => 'CNC-FIT',
        'SERVICE FLANGE' => 'SRV-FL',
        'BUBUT OTOMATIS' => 'BBT-OT',
        'QC SOLDER' => 'QC-SLD',
        'BOR FLANGE' => 'BOR-FL',
        'CNC FLANGE' => 'CNC-FL',
        'UMUM' => 'UMUM',
        'MAINTENANCE' => 'MNT',
        'MAINTENANCE FITTING' => 'MNT-FIT',
        'MAINTENANCE COR FLANGE' => 'MNT-COR-FL',
        'MAINTENANCE BUBUT CNC' => 'MNT-BBT-CNC',
        'QC FITTING' => 'QC-FIT',
        'QA/QC' => 'QA-QC',
        'QA FITTING' => 'QA-FIT',
        'QA FLANGE' => 'QA-FL',
        'MILLING CNC' => 'MIL-CNC',
        'BESI' => 'BESI',
        'ALUMINIUM' => 'AL',
        'COR FITTING' => 'COR-FIT',
        'FITTING' => 'FIT',
        'LAPISAN' => 'LPS',
        'MARKING' => 'MRK',
        'LILIN' => 'LLN',
        'SECURITY' => 'SEC',
    ];

    private array $personnel = [
        ['name' => 'AFIN', 'jabatan' => 'MANAGER', 'area' => 'PPIC', 'dept' => 'PPIC'],
        ['name' => 'EKO', 'jabatan' => 'STAFF', 'area' => 'PPIC', 'dept' => 'PPIC'],
        ['name' => 'IKA', 'jabatan' => 'STAFF', 'area' => 'PPIC', 'dept' => 'PPIC'],
        ['name' => 'AGRIN', 'jabatan' => 'STAFF', 'area' => 'IT DAN K3', 'dept' => 'HRD'],
        ['name' => 'NISA', 'jabatan' => 'STAFF', 'area' => 'CCTV', 'dept' => 'HRD'],
        ['name' => 'ROKI', 'jabatan' => 'STAFF', 'area' => 'HR', 'dept' => 'HRD'],
        ['name' => 'RIKI', 'jabatan' => 'SPV', 'area' => 'BAHAN BAKU', 'dept' => 'PPIC'],
        ['name' => 'HERMAN', 'jabatan' => 'SPV', 'area' => 'GUDANG FLANGE', 'dept' => 'PPIC'],
        ['name' => 'DANI', 'jabatan' => 'SPV', 'area' => 'GUDANG FITTING', 'dept' => 'PPIC'],
        ['name' => 'RUKASIM', 'jabatan' => 'KABAG', 'area' => 'COR FLANGE', 'dept' => 'PRODUKSI FLANGE'],
        ['name' => 'ROJI/MAJID', 'jabatan' => 'SPV', 'area' => 'COR FLANGE', 'dept' => 'PRODUKSI FLANGE'],
        ['name' => 'HUDA', 'jabatan' => 'SPV', 'area' => 'NETTO FLANGE', 'dept' => 'PRODUKSI FLANGE'],
        ['name' => 'HUDA', 'jabatan' => 'SPV', 'area' => 'NETTO FITTING', 'dept' => 'PRODUKSI FITTING'],
        ['name' => 'AININ', 'jabatan' => 'WAKIL SPV', 'area' => 'KIMIA', 'dept' => 'PRODUKSI FITTING'],
        ['name' => 'ALFIAN', 'jabatan' => 'KABAG', 'area' => 'CNC FITTING', 'dept' => 'PRODUKSI FITTING'],
        ['name' => 'EDI', 'jabatan' => 'KABAG', 'area' => 'SERVICE FLANGE', 'dept' => 'PRODUKSI FLANGE'],
        ['name' => 'SODIQ/BAMBANG', 'jabatan' => 'SPV', 'area' => 'BUBUT OTOMATIS', 'dept' => 'PRODUKSI FLANGE'],
        ['name' => 'NURI', 'jabatan' => 'SPV', 'area' => 'QC SOLDER', 'dept' => 'PRODUKSI FLANGE'],
        ['name' => 'TEGUH', 'jabatan' => 'SPV', 'area' => 'BOR FLANGE', 'dept' => 'PRODUKSI FLANGE'],
        ['name' => 'SAHRUL/BAGUS', 'jabatan' => 'SPV', 'area' => 'CNC FLANGE', 'dept' => 'PRODUKSI FLANGE'],
        ['name' => 'ARSENG', 'jabatan' => 'SPV', 'area' => 'UMUM', 'dept' => 'UMUM'],
        ['name' => 'TRI HARDI', 'jabatan' => 'KABAG', 'area' => 'MAINTENANCE', 'dept' => 'MAINTENANCE'],
        ['name' => 'DENY', 'jabatan' => 'SPV', 'area' => 'MAINTENANCE FITTING', 'dept' => 'MAINTENANCE'],
        ['name' => 'FIRNANDA', 'jabatan' => 'SPV', 'area' => 'MAINTENANCE COR FLANGE', 'dept' => 'MAINTENANCE'],
        ['name' => 'UTOMO', 'jabatan' => 'SPV', 'area' => 'MAINTENANCE BUBUT CNC', 'dept' => 'MAINTENANCE'],
        ['name' => 'ARIS', 'jabatan' => 'SPV', 'area' => 'QC FITTING', 'dept' => 'QA/QC'],
        ['name' => 'YAYAK', 'jabatan' => 'MANAGER', 'area' => 'QA/QC', 'dept' => 'QA/QC'],
        ['name' => 'ANDRE', 'jabatan' => 'STAFF', 'area' => 'QA FITTING', 'dept' => 'QA/QC'],
        ['name' => 'ADI', 'jabatan' => 'STAFF', 'area' => 'QA FLANGE', 'dept' => 'QA/QC'],
        ['name' => 'FAISAL', 'jabatan' => 'SPV', 'area' => 'MILLING CNC', 'dept' => 'MILLING CNC'],
        ['name' => 'EKO RIRIT', 'jabatan' => 'SPV', 'area' => 'BESI', 'dept' => 'PRODUKSI FLANGE'],
        ['name' => 'ULIL', 'jabatan' => 'SPV', 'area' => 'ALUMINIUM', 'dept' => 'ALUMINIUM'],
        ['name' => 'DWIAN', 'jabatan' => 'KABAG', 'area' => 'COR FITTING', 'dept' => 'PRODUKSI FITTING'],
        ['name' => 'RAVY', 'jabatan' => 'STAFF', 'area' => 'FITTING', 'dept' => 'PRODUKSI FITTING'],
        ['name' => 'LINGGA', 'jabatan' => 'SPV', 'area' => 'LAPISAN', 'dept' => 'PRODUKSI FITTING'],
        ['name' => 'AGUS', 'jabatan' => 'SPV', 'area' => 'MARKING', 'dept' => 'PRODUKSI FLANGE'],
        ['name' => 'DEVI', 'jabatan' => 'SPV', 'area' => 'LILIN', 'dept' => 'PRODUKSI FITTING'],
        ['name' => 'JOKO', 'jabatan' => 'SPV', 'area' => 'FITTING', 'dept' => 'PRODUKSI FITTING'],
        ['name' => 'ROUD', 'jabatan' => 'KABAG', 'area' => 'SECURITY', 'dept' => 'HRD'],
    ];

    public function run(): void
    {
        $seededUsers = [];
        $managers = [];
        $kabagsByDept = [];

        // Phase 1: Create all users
        foreach ($this->personnel as $row) {
            $names = strpos($row['name'], '/') !== false ? explode('/', $row['name']) : [$row['name']];

            foreach ($names as $name) {
                // Skip if already seeded (e.g. HUDA, though we update assignments below)
                if (isset($seededUsers[$name])) {
                    continue;
                }

                $email = strtolower(str_replace(' ', '', $name)).'@peroniks.com';
                $role = 'spv';
                if ($row['jabatan'] === 'MANAGER') {
                    $role = 'manager';
                } elseif ($row['jabatan'] === 'KABAG') {
                    $role = 'kabag';
                }

                $deptCode = $this->deptNameToCode[$row['dept']];
                $dept = Department::where('code', $deptCode)->firstOrFail();

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => Hash::make('password'),
                        'role' => $role,
                        'department_id' => $dept->id,
                        'department_name' => $dept->name,
                    ]
                );

                $seededUsers[$name] = $user;

                if ($role === 'manager') {
                    $managers[] = $user;
                } elseif ($role === 'kabag') {
                    $kabagsByDept[$deptCode][] = $user;
                }
            }
        }

        // Clean up users that are no longer part of the master (except auth users)
        $authEmails = [
            'adminppic@peroniks.com',
            'mr@peroniks.com',
            'direktur@peroniks.com',
            'admin@kaizen.com',
            'spv_a@kaizen.com',
        ];
        $seededEmails = array_map(function ($u) {
            return $u->email;
        }, $seededUsers);
        User::whereNotIn('email', array_merge($authEmails, $seededEmails))->delete();

        // Phase 2: Set up manager hierarchy
        foreach ($this->personnel as $row) {
            $names = strpos($row['name'], '/') !== false ? explode('/', $row['name']) : [$row['name']];
            foreach ($names as $name) {
                $user = $seededUsers[$name];
                $deptCode = $this->deptNameToCode[$row['dept']];

                $managerId = null;

                if ($user->role === 'manager') {
                    $managerId = null;
                } elseif ($user->role === 'kabag') {
                    $managerId = ! empty($managers) ? $managers[0]->id : null;
                } else {
                    if (! empty($kabagsByDept[$deptCode])) {
                        $managerId = $kabagsByDept[$deptCode][0]->id;
                    } else {
                        $deptManager = User::where('role', 'manager')->where('department_id', $user->department_id)->first();
                        $managerId = $deptManager ? $deptManager->id : (! empty($managers) ? $managers[0]->id : null);
                    }
                }

                $user->update(['manager_id' => $managerId]);
            }
        }

        // Phase 3: Setup Area Assignments
        AreaAssignment::query()->delete();

        foreach ($this->personnel as $row) {
            $names = strpos($row['name'], '/') !== false ? explode('/', $row['name']) : [$row['name']];
            foreach ($names as $name) {
                $user = $seededUsers[$name];
                $areaCode = $this->areaNameToCode[$row['area']];
                $area = Area::where('code', $areaCode)->firstOrFail();

                $roleEnum = Position::Spv;
                if ($row['jabatan'] === 'MANAGER') {
                    $roleEnum = Position::Manager;
                } elseif ($row['jabatan'] === 'KABAG') {
                    $roleEnum = Position::Kabag;
                }

                // HUDA appears twice, so we do AreaAssignment::updateOrCreate to support multiple assignments
                AreaAssignment::updateOrCreate(
                    [
                        'area_id' => $area->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role' => $roleEnum,
                        'started_at' => '2026-01-01',
                    ]
                );
            }
        }
    }
}
