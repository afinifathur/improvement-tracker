<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWeeklyStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|in:completed,completed_no_impact,not_completed,extended',
            'notes' => 'nullable|string|required_if:status,extended',
            'proofs' => 'nullable|array',
            'proofs.*' => 'image|max:10240', // 10MB max
            'category_corrected' => 'nullable|in:improvement,problem,maintenance',
            'week_end_date' => 'required_if:status,extended|date',
        ];
    }

    /**
     * Get the validation messages for the defined rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proofs.required_if' => 'Bukti pekerjaan wajib diisi ketika status disetel ke selesai.',
        ];
    }
}
