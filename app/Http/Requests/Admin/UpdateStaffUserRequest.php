<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdminModule('staff') ?? false;
    }

    public function rules(): array
    {
        /** @var User $staffUser */
        $staffUser = $this->route('staffUser');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staffUser->id)],
            'password' => ['nullable', Password::min(8)],
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
