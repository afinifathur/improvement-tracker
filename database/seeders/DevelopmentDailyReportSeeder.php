<?php

namespace Database\Seeders;

use App\Models\DailyReport;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DevelopmentDailyReportSeeder extends Seeder
{
    private const DATE = '2026-08-14';

    public function run(): void
    {
        // Force delete existing reports for target date to start clean
        DailyReport::whereDate('report_date', self::DATE)->delete();

        $admin = User::where('email', 'adminppic@peroniks.com')->first();

        if (! $admin) {
            return;
        }

        // Take exactly 30 reporters (non-managers) to satisfy the test expectation (28 to 35)
        $reporters = User::whereIn('role', ['spv', 'kabag'])->orderBy('id')->take(30)->get();

        $templates = [
            [
                ['t' => 'Review operational tasks', 's' => 'completed', 'a' => '2026-08-13', 'b' => '2026-08-14'],
                ['t' => 'Follow up outstanding items', 's' => 'in_progress', 'a' => '2026-08-14', 'b' => '2026-08-18'],
                ['t' => 'Prepare weekly report planning', 's' => 'not_started', 'a' => '2026-08-15', 'b' => '2026-08-17'],
                ['t' => 'Ad-hoc request from supervisor', 's' => 'cancelled', 'a' => '2026-08-13', 'b' => '2026-08-14', 'cr' => 'customer_cancelled'],
            ],
            [
                ['t' => 'Resolve machine breakdown issue', 's' => 'in_progress', 'a' => '2026-08-11', 'b' => '2026-08-13'],
                ['t' => 'Follow up vendor material', 's' => 'blocked', 'a' => '2026-08-12', 'b' => '2026-08-13', 'br' => 'waiting_material'],
                ['t' => 'Quality checking checklist', 's' => 'completed', 'a' => '2026-08-14', 'b' => '2026-08-14'],
                ['t' => 'Setup tooling and fixtures', 's' => 'in_progress', 'a' => '2026-08-14', 'b' => '2026-08-15'],
            ],
            [
                ['t' => 'Calibrate measurement tools', 's' => 'completed', 'a' => '2026-08-12', 'b' => '2026-08-13'],
                ['t' => 'Execute routine audit checks', 's' => 'in_progress', 'a' => '2026-08-14', 'b' => '2026-08-15'],
                ['t' => 'Review defect logs with team', 's' => 'blocked', 'a' => '2026-08-14', 'b' => '2026-08-16', 'br' => 'waiting_vendor'],
            ],
            [
                ['t' => 'Audit warehouse stock inventory', 's' => 'completed', 'a' => '2026-08-13', 'b' => '2026-08-14'],
                ['t' => 'Organize floor layout optimization', 's' => 'in_progress', 'a' => '2026-08-14', 'b' => '2026-08-15'],
                ['t' => 'Draft safety training outline', 's' => 'not_started', 'a' => '2026-08-16', 'b' => '2026-08-18'],
            ],
            [
                ['t' => 'Clean CNC machine filter units', 's' => 'completed', 'a' => '2026-08-12', 'b' => '2026-08-14'],
                ['t' => 'Troubleshoot sensor alignment', 's' => 'blocked', 'a' => '2026-08-14', 'b' => '2026-08-15', 'br' => 'waiting_approval'],
                ['t' => 'Conduct daily shift briefing', 's' => 'not_started', 'a' => '2026-08-15', 'b' => '2026-08-17'],
            ],
        ];

        foreach ($reporters as $index => $reporter) {
            $template = $templates[$index % count($templates)];
            $assignment = $reporter->areaAssignments()->first();
            $areaId = $assignment?->area_id;

            $report = DailyReport::create([
                'reported_by' => $reporter->id,
                'report_date' => self::DATE,
                'department_id' => $reporter->department_id,
                'area_id' => $areaId,
                'today_result' => 'Seeded daily progress report summary.',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            foreach ($template as $item) {
                $data = [
                    'title' => $item['t'],
                    'owner_id' => $reporter->id,
                    'department_id' => $reporter->department_id,
                    'area_id' => $areaId,
                    'original_start_date' => $item['a'],
                    'original_end_date' => $item['b'],
                    'planned_start_date' => $item['a'],
                    'planned_end_date' => $item['b'],
                    'status' => $item['s'],
                    'source_daily_report_id' => $report->id,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ];

                if ($item['s'] === 'completed') {
                    $data['completed_at'] = Carbon::parse($item['b'])->setTime(17, 0);
                }

                if ($item['s'] === 'blocked') {
                    $data['blocked_reason'] = $item['br'];
                    $data['blocked_reason_note'] = 'Awaiting dependency resolution.';
                    $data['blocked_at'] = Carbon::parse($item['a'])->setTime(10, 0);
                }

                if ($item['s'] === 'cancelled') {
                    $data['cancel_reason'] = $item['cr'];
                    $data['cancel_reason_note'] = 'Cancelled per operations request.';
                }

                WorkItem::create($data);
            }
        }
    }
}
