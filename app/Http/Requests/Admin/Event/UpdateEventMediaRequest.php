<?php

namespace App\Http\Requests\Admin\Event;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cover_image' => ['nullable', 'image', 'max:6144'],
            'wizard_action' => ['nullable', 'in:draft,continue'],
        ];
    }
}
