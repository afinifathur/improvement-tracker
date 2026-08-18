<?php

namespace App\Http\Requests;

use App\Models\WeeklyPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDailyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return array_merge(
            [
                'report_date' => 'required|date',
                'reported_by' => 'required|exists:users,id',
                'area_id' => [
                    'required',
                    'exists:areas,id',
                    Rule::unique('daily_reports')
                        ->where(fn ($query) => $query
                            ->where('reported_by', $this->input('reported_by'))
                            ->whereDate('report_date', $this->input('report_date'))),
                ],
                'today_result' => 'nullable|string',
                'work_items' => 'nullable|array',
            ],
            $this->workItemRules()
        );
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $weeklyPlanIds = collect($this->input('work_items', []))
                    ->pluck('weekly_plan_id')
                    ->filter()
                    ->unique();

                $plans = [];
                if ($weeklyPlanIds->isNotEmpty()) {
                    $plans = WeeklyPlan::whereIn('id', $weeklyPlanIds)->get()->keyBy('id');
                }

                $reportedBy = (int) $this->input('reported_by');

                foreach ($this->input('work_items', []) as $index => $item) {
                    if (
                        isset($item['planned_start_date'], $item['planned_end_date'])
                        && $item['planned_start_date'] !== ''
                        && $item['planned_end_date'] !== ''
                        && $item['planned_end_date'] < $item['planned_start_date']
                    ) {
                        $validator->errors()->add(
                            "work_items.$index.planned_end_date",
                            'Tanggal selesai harus sama dengan atau setelah tanggal mulai.'
                        );
                    }

                    $planId = $item['weekly_plan_id'] ?? null;
                    if ($planId && isset($plans[$planId])) {
                        if ($plans[$planId]->user_id !== $reportedBy) {
                            $validator->errors()->add(
                                "work_items.$index.weekly_plan_id",
                                'Rencana mingguan ini bukan milik personel terpilih.'
                            );
                        }
                    }
                }
            },
        ];
    }

    protected function workItemRules(): array
    {
        return [
            'work_items.*.id' => 'nullable|exists:work_items,id',
            'work_items.*.title' => 'required|string|max:255',
            'work_items.*.description' => 'nullable|string',
            'work_items.*.planned_start_date' => 'required|date',
            'work_items.*.planned_end_date' => 'required|date',
            'work_items.*.weekly_plan_id' => 'nullable|exists:weekly_plans,id',
            'work_items.*.status' => 'nullable|string|in:not_started,in_progress,blocked,completed,cancelled',
        ];
    }
}
