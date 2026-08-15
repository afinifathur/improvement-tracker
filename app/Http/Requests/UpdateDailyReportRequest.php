<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDailyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return array_merge(
            [
                'today_result' => 'nullable|string',
                'work_items' => 'nullable|array',
            ],
            [
                'work_items.*.title' => 'required|string|max:255',
                'work_items.*.description' => 'nullable|string',
                'work_items.*.planned_start_date' => 'required|date',
                'work_items.*.planned_end_date' => 'required|date',
            ]
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
}
