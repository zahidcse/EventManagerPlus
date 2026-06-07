<?php

namespace App\Http\Requests\Admin\Event;

use App\Support\Edition;
use App\Support\TimezoneList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventBasicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $scheduleTypes = Edition::allowsRecurringSchedule()
            ? ['single', 'recurring', 'custom_interval']
            : ['single'];

        $rules = [
            'organizer_id' => ['nullable', 'integer', 'exists:organizers,id'],
            'title' => ['required', 'string', 'max:255'],
            'event_category_id' => ['nullable', 'integer', 'exists:event_categories,id'],
            'visibility' => ['required', 'in:public,private'],
            'description' => ['nullable', 'string', 'max:100000'],
            'start_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_date' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'timezone' => ['required', 'string', 'max:64', Rule::in(TimezoneList::identifiers())],
            'schedule_type' => ['required', Rule::in($scheduleTypes)],
            'location_type' => ['required', 'string', 'in:physical,virtual,hybrid'],
            'venue_street' => ['nullable', 'string', 'max:10000'],
            'streaming_platform' => ['nullable', 'string', 'in:zoom,teams,google_meet,custom'],
            'meeting_url' => ['nullable', 'string', 'url', 'max:2048'],
            'event_speakers' => ['nullable', 'array'],
            'event_speakers.*.speaker_id' => ['nullable', 'integer', 'exists:speakers,id'],
        ];

        if (Edition::allowsRecurringSchedule()) {
            $rules['recurrence_weekdays'] = ['exclude_unless:schedule_type,recurring', 'required', 'array', 'min:1'];
            $rules['recurrence_weekdays.*'] = ['integer', 'between:0,6'];
            $rules['recurrence_ends_on'] = ['nullable', 'date'];
            $rules['custom_schedule_dates'] = ['exclude_unless:schedule_type,custom_interval', 'required', 'array', 'min:1'];
            $rules['custom_schedule_dates.*'] = ['date'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('organizer_id') === '' || $this->input('organizer_id') === null) {
            $this->merge(['organizer_id' => null]);
        }

        if ($this->input('event_category_id') === '' || $this->input('event_category_id') === null) {
            $this->merge(['event_category_id' => null]);
        }

        foreach (['streaming_platform', 'meeting_url', 'venue_street'] as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }

        if (! Edition::allowsRecurringSchedule()) {
            $this->merge([
                'schedule_type' => 'single',
                'recurrence_weekdays' => [],
                'recurrence_ends_on' => null,
                'repeat_every_days' => null,
                'custom_schedule_dates' => [],
            ]);

            return;
        }

        $st = $this->input('schedule_type');
        if (! in_array($st, ['single', 'recurring', 'custom_interval'], true)) {
            $this->merge(['schedule_type' => 'single']);
            $st = 'single';
        }
        if ($st !== 'recurring') {
            $this->merge(['recurrence_weekdays' => []]);
        }
        if ($st !== 'custom_interval') {
            $this->merge(['repeat_every_days' => null, 'custom_schedule_dates' => []]);
        }
        if ($st === 'custom_interval') {
            $rawDates = $this->input('custom_schedule_dates');
            if (! is_array($rawDates)) {
                $rawDates = [];
            }
            $this->merge([
                'custom_schedule_dates' => array_values(array_filter(
                    $rawDates,
                    static fn ($d) => $d !== null && $d !== ''
                )),
            ]);
        }
        if ($this->input('recurrence_ends_on') === '') {
            $this->merge(['recurrence_ends_on' => null]);
        }
        if (! TimezoneList::isValid($this->input('timezone'))) {
            $this->merge(['timezone' => 'UTC']);
        }
    }
}
