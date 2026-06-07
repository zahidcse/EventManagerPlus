<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdminModule('staff') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'admin_role_id' => [
                'required',
                'integer',
                Rule::exists('admin_roles', 'id')->where(function ($query): void {
                    $query->where('is_super', false)
                        ->whereIn('audience', ['staff', 'both']);
                }),
            ],
            'timezone' => ['nullable', 'string', 'max:64'],
        ];
    }
}
