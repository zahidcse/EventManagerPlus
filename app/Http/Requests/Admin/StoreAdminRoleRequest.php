<?php

namespace App\Http\Requests\Admin;

use App\Support\Admin\AdminModules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdminModule('staff') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', 'alpha_dash', 'unique:admin_roles,slug'],
            'audience' => ['required', 'in:staff,organizer,both'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(AdminModules::keys())],
        ];
    }
}
