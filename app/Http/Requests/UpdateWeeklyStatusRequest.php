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
            'notes' => 'nullable|string',
            'proofs' => 'required_if:status,completed,completed_no_impact|array|min:1',
            'proofs.*' => 'image|max:10240', // 10MB max
            'category_corrected' => 'nullable|in:improvement,problem,maintenance',
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
            'proofs.required_if' => 'Proof of work is required when status is set to completed.',
        ];
    }
}
