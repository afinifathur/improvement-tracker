<?php

namespace App\Http\Requests;

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
                foreach ($this->input('work_items', []) as $index => $item) {
                    if (
                        isset($item['planned_start_date'], $item['planned_end_date'])
                        && $item['planned_start_date'] !== ''
                        && $item['planned_end_date'] !== ''
                        && $item['planned_end_date'] < $item['planned_start_date']
                    ) {
                        $validator->errors()->add(
                            "work_items.$index.planned_end_date",
                            'The planned end date must be on or after the planned start date.'
                        );
                    }
                }
            },
        ];
    }

    protected function workItemRules(): array
    {
        return [
            'work_items.*.title' => 'required|string|max:255',
            'work_items.*.description' => 'nullable|string',
            'work_items.*.planned_start_date' => 'required|date',
            'work_items.*.planned_end_date' => 'required|date',
        ];
    }
}
