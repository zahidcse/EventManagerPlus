<?php

namespace App\Http\Requests\Admin;

use App\Support\PersonName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreOrganizerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => PersonName::format($this->input('name')),
            'job_title' => filled($this->input('job_title'))
                ? Str::title(trim((string) $this->input('job_title')))
                : null,
            'company_name' => trim((string) $this->input('company_name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:organizers,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', Password::min(8)],
            'bio' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'country' => ['nullable', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', 'in:active,inactive'],
            'admin_panel_access' => ['sometimes', 'boolean'],
            'admin_role_id' => [
                'nullable',
                'integer',
                Rule::exists('admin_roles', 'id')->where(function ($query): void {
                    $query->where('is_super', false)
                        ->whereIn('audience', ['organizer', 'both']);
                }),
                Rule::requiredIf(fn () => $this->boolean('admin_panel_access')),
            ],
        ];
    }
}
