<?php

namespace App\Http\Requests\Admin;

use App\Models\AdminRole;
use App\Support\Admin\AdminModules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdminModule('staff') ?? false;
    }

    public function rules(): array
    {
        /** @var AdminRole $role */
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('admin_roles', 'slug')->ignore($role->id)],
            'audience' => ['required', 'in:staff,organizer,both'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(AdminModules::keys())],
        ];
    }
}
