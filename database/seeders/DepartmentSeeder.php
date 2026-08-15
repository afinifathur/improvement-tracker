<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Area;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    private array $departments = [
        ['code' => 'PPIC', 'name' => 'PPIC'],
        ['code' => 'HRD', 'name' => 'HRD'],
        ['code' => 'PRD-FL', 'name' => 'PRODUKSI FLANGE'],
        ['code' => 'PRD-FIT', 'name' => 'PRODUKSI FITTING'],
        ['code' => 'UMUM', 'name' => 'UMUM'],
        ['code' => 'MTC', 'name' => 'MAINTENANCE'],
        ['code' => 'QA-QC', 'name' => 'QA/QC'],
        ['code' => 'CNC', 'name' => 'MILLING CNC'],
        ['code' => 'AL', 'name' => 'ALUMINIUM'],
    ];

    private array $areas = [
        ['code' => 'PPIC', 'name' => 'PPIC', 'dept_code' => 'PPIC'],
        ['code' => 'IT-K3', 'name' => 'IT DAN K3', 'dept_code' => 'HRD'],
        ['code' => 'CCTV', 'name' => 'CCTV', 'dept_code' => 'HRD'],
        ['code' => 'HR', 'name' => 'HR', 'dept_code' => 'HRD'],
        ['code' => 'BHN-BK', 'name' => 'BAHAN BAKU', 'dept_code' => 'PPIC'],
        ['code' => 'GD-FL', 'name' => 'GUDANG FLANGE', 'dept_code' => 'PPIC'],
        ['code' => 'GD-FIT', 'name' => 'GUDANG FITTING', 'dept_code' => 'PPIC'],
        ['code' => 'COR-FL', 'name' => 'COR FLANGE', 'dept_code' => 'PRD-FL'],
        ['code' => 'NT-FL', 'name' => 'NETTO FLANGE', 'dept_code' => 'PRD-FL'],
        ['code' => 'NT-FIT', 'name' => 'NETTO FITTING', 'dept_code' => 'PRD-FIT'],
        ['code' => 'KM', 'name' => 'KIMIA', 'dept_code' => 'PRD-FIT'],
        ['code' => 'CNC-FIT', 'name' => 'CNC FITTING', 'dept_code' => 'PRD-FIT'],
        ['code' => 'SRV-FL', 'name' => 'SERVICE FLANGE', 'dept_code' => 'PRD-FL'],
        ['code' => 'BBT-OT', 'name' => 'BUBUT OTOMATIS', 'dept_code' => 'PRD-FL'],
        ['code' => 'QC-SLD', 'name' => 'QC SOLDER', 'dept_code' => 'PRD-FL'],
        ['code' => 'BOR-FL', 'name' => 'BOR FLANGE', 'dept_code' => 'PRD-FL'],
        ['code' => 'CNC-FL', 'name' => 'CNC FLANGE', 'dept_code' => 'PRD-FL'],
        ['code' => 'UMUM', 'name' => 'UMUM', 'dept_code' => 'UMUM'],
        ['code' => 'MNT', 'name' => 'MAINTENANCE', 'dept_code' => 'MTC'],
        ['code' => 'MNT-FIT', 'name' => 'MAINTENANCE FITTING', 'dept_code' => 'MTC'],
        ['code' => 'MNT-COR-FL', 'name' => 'MAINTENANCE COR FLANGE', 'dept_code' => 'MTC'],
        ['code' => 'MNT-BBT-CNC', 'name' => 'MAINTENANCE BUBUT CNC', 'dept_code' => 'MTC'],
        ['code' => 'QC-FIT', 'name' => 'QC FITTING', 'dept_code' => 'QA-QC'],
        ['code' => 'QA-QC', 'name' => 'QA/QC', 'dept_code' => 'QA-QC'],
        ['code' => 'QA-FIT', 'name' => 'QA FITTING', 'dept_code' => 'QA-QC'],
        ['code' => 'QA-FL', 'name' => 'QA FLANGE', 'dept_code' => 'QA-QC'],
        ['code' => 'MIL-CNC', 'name' => 'MILLING CNC', 'dept_code' => 'CNC'],
        ['code' => 'BESI', 'name' => 'BESI', 'dept_code' => 'PRD-FL'],
        ['code' => 'AL', 'name' => 'ALUMINIUM', 'dept_code' => 'AL'],
        ['code' => 'COR-FIT', 'name' => 'COR FITTING', 'dept_code' => 'PRD-FIT'],
        ['code' => 'FIT', 'name' => 'FITTING', 'dept_code' => 'PRD-FIT'],
        ['code' => 'LPS', 'name' => 'LAPISAN', 'dept_code' => 'PRD-FIT'],
        ['code' => 'MRK', 'name' => 'MARKING', 'dept_code' => 'PRD-FL'],
        ['code' => 'LLN', 'name' => 'LILIN', 'dept_code' => 'PRD-FIT'],
        ['code' => 'SEC', 'name' => 'SECURITY', 'dept_code' => 'HRD'],
    ];

    public function run(): void
    {
        $deptCodes = array_column($this->departments, 'code');
        Department::whereNotIn('code', $deptCodes)->delete();

        foreach ($this->departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name']]
            );
        }

        $areaCodes = array_column($this->areas, 'code');
        Area::whereNotIn('code', $areaCodes)->delete();

        foreach ($this->areas as $areaSpec) {
            $dept = Department::where('code', $areaSpec['dept_code'])->firstOrFail();
            Area::updateOrCreate(
                ['code' => $areaSpec['code']],
                [
                    'name' => $areaSpec['name'],
                    'department_id' => $dept->id,
                ]
            );
        }
    }
}
