<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'effective_date' => 'required|date',
            'reason' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
        ];
    }
}
