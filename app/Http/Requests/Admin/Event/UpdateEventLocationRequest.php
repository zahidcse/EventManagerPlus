<?php

namespace App\Http\Requests\Admin\Event;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['streaming_platform', 'meeting_url', 'venue_street', 'venue_city', 'venue_state', 'venue_postal', 'venue_country'] as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $merge[$key] = null;
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'location_type' => ['required', 'string', 'in:physical,virtual,hybrid'],
            'venue_street' => ['nullable', 'string', 'max:10000'],
            'venue_city' => ['nullable', 'string', 'max:255'],
            'venue_state' => ['nullable', 'string', 'max:255'],
            'venue_postal' => ['nullable', 'string', 'max:64'],
            'venue_country' => ['nullable', 'string', 'max:255'],
            'streaming_platform' => ['nullable', 'string', 'in:zoom,teams,google_meet,custom'],
            'meeting_url' => ['nullable', 'string', 'url', 'max:2048'],
            'wizard_action' => ['nullable', 'in:draft,continue'],
        ];
    }
}
