<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreWeeklyPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    /**
     * Normalize the selected date into a full Monday–Sunday week.
     *
     * The client submits a single date; the server derives the week start
     * (Monday) and week end (Sunday) so an arbitrary range can never be
     * persisted. Any client-supplied week_end_date is discarded.
     */
    protected function prepareForValidation(): void
    {
        $date = $this->input('week_start_date');

        if (! $date) {
            return;
        }

        try {
            $start = Carbon::parse($date)->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable) {
            return;
        }

        $this->merge([
            'week_start_date' => $start->toDateString(),
            'week_end_date' => $start->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('id', function ($sub) {
                        $sub->select('user_id')->from('area_assignments');
                    });
                }),
            ],
            'title' => 'required|string|max:255',
            'expected_output' => 'required|string|min:10',
            'category' => 'required|in:improvement,problem,maintenance',
            'impact_level' => 'required|in:low,medium,high',
            'week_start_date' => 'required|date',
            'week_end_date' => 'required|date|after:week_start_date',
        ];
    }
}
