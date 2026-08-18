<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\WorkItem;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    /**
     * Persist a daily report and its new work items atomically.
     */
    public function store(array $data, int $actorId): DailyReport
    {
        return DB::transaction(function () use ($data, $actorId) {
            $report = DailyReport::create([
                'report_date' => $data['report_date'],
                'reported_by' => $data['reported_by'],
                'area_id' => $data['area_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'today_result' => $data['today_result'] ?? null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->persistWorkItems(
                $report,
                $data['work_items'] ?? [],
                $data['reported_by'],
                $data['area_id'] ?? null,
                $data['department_id'] ?? null,
                $actorId
            );

            return $report;
        });
    }

    /**
     * Update the report narrative and update/append work items safely.
     */
    public function update(DailyReport $report, array $data, int $actorId): DailyReport
    {
        return DB::transaction(function () use ($report, $data, $actorId) {
            $report->update([
                'today_result' => $data['today_result'] ?? null,
                'updated_by' => $actorId,
            ]);

            $this->persistWorkItems(
                $report,
                $data['work_items'] ?? [],
                $report->reported_by,
                $report->area_id,
                $report->department_id,
                $actorId
            );

            return $report;
        });
    }

    private function persistWorkItems(
        DailyReport $report,
        array $items,
        int $ownerId,
        ?int $areaId,
        ?int $departmentId,
        int $actorId
    ): void {
        foreach ($items as $item) {
            $start = $item['planned_start_date'];
            $end = $item['planned_end_date'];
            $status = $item['status'] ?? 'not_started';

            $timestamps = [];
            if ($status === 'completed') {
                $timestamps['completed_at'] = now();
            } else {
                $timestamps['completed_at'] = null;
            }

            if ($status === 'blocked') {
                $timestamps['blocked_at'] = now();
            } else {
                $timestamps['blocked_at'] = null;
            }

            if (!empty($item['id'])) {
                $existingItem = WorkItem::findOrFail($item['id']);
                
                $updateData = array_merge([
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'planned_start_date' => $start,
                    'planned_end_date' => $end,
                    'weekly_plan_id' => $item['weekly_plan_id'] ?? null,
                    'status' => $status,
                    'updated_by' => $actorId,
                ], $timestamps);

                $existingItem->update($updateData);
            } else {
                WorkItem::create(array_merge([
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'owner_id' => $ownerId,
                    'area_id' => $areaId,
                    'department_id' => $departmentId,
                    'original_start_date' => $start,
                    'original_end_date' => $end,
                    'planned_start_date' => $start,
                    'planned_end_date' => $end,
                    'source_daily_report_id' => $report->id,
                    'weekly_plan_id' => $item['weekly_plan_id'] ?? null,
                    'status' => $status,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ], $timestamps));
            }
        }
    }
}
