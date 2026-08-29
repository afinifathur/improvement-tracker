<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,director,manager,kabag,spv',
            'department_id' => 'nullable|exists:departments,id',
            'area_id' => 'nullable|exists:areas,id',
            'position' => 'nullable|in:manager,kabag,spv',
        ];
    }
}
