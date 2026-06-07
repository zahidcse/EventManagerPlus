<?php

namespace App\Http\Requests\Admin\Event;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventSpeakersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_speakers' => ['nullable', 'array'],
            'event_speakers.*.speaker_id' => ['nullable', 'integer', 'exists:speakers,id'],
            'wizard_action' => ['nullable', 'string', 'in:draft,continue'],
        ];
    }
}
