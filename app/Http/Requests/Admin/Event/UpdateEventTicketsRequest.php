<?php

namespace App\Http\Requests\Admin\Event;

use App\Support\Edition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'global_ticket_quantity_enabled' => $this->boolean('global_ticket_quantity_enabled'),
        ]);

        $coupons = $this->input('coupons', []);
        if (! is_array($coupons)) {
            return;
        }
        foreach ($coupons as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            if (($row['id'] ?? '') === '' || $row['id'] === null) {
                $coupons[$i]['id'] = null;
            }
        }
        $this->merge(['coupons' => $coupons]);

        if (! Edition::allowsAdditionalServices()) {
            $this->merge(['additional_services' => []]);
        }

        if (! Edition::allowsEarlyBirdPricing()) {
            $tickets = $this->input('tickets', []);
            if (is_array($tickets)) {
                foreach ($tickets as $i => $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    unset(
                        $tickets[$i]['early_bird_price'],
                        $tickets[$i]['early_bird_ends_at'],
                        $tickets[$i]['sales_start'],
                    );
                }
                $this->merge(['tickets' => $tickets]);
            }
        }
    }

    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'tickets' => ['nullable', 'array'],
            'tickets.*.name' => ['nullable', 'string', 'max:255'],
            'tickets.*.price' => ['nullable', 'numeric', 'min:0'],
            'tickets.*.quantity' => ['nullable', 'integer', 'min:0'],
            'tickets.*.sales_start' => ['nullable', 'date'],
            'tickets.*.sales_end' => ['nullable', 'date'],
            'tickets.*.early_bird_price' => ['nullable', 'numeric', 'min:0'],
            'tickets.*.early_bird_ends_at' => ['nullable', 'date'],
            'additional_services' => ['nullable', 'array'],
            'additional_services.*.name' => ['nullable', 'string', 'max:255'],
            'additional_services.*.price' => ['nullable', 'numeric', 'min:0'],
            'additional_services.*.quantity' => ['nullable', 'integer', 'min:0'],
            'coupons' => ['nullable', 'array'],
            'coupons.*.id' => [
                'nullable',
                'integer',
                Rule::exists('event_coupons', 'id')->where('event_id', $event->id),
            ],
            'coupons.*.code' => ['nullable', 'string', 'max:64'],
            'coupons.*.discount_type' => ['nullable', 'in:percent,fixed'],
            'coupons.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'coupons.*.max_uses' => ['nullable', 'integer', 'min:0'],
            'coupons.*.valid_from' => ['nullable', 'date'],
            'coupons.*.valid_until' => ['nullable', 'date'],
            'coupons.*.is_active' => ['nullable', 'boolean'],
            'global_ticket_quantity_enabled' => ['sometimes', 'boolean'],
            'global_ticket_quantity' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $coupons = $this->input('coupons', []);
            if (! is_array($coupons)) {
                return;
            }
            $codes = [];
            foreach ($coupons as $idx => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $code = strtoupper(trim((string) ($row['code'] ?? '')));
                if ($code === '') {
                    continue;
                }
                if (isset($codes[$code])) {
                    $validator->errors()->add('coupons', 'Each coupon code must be unique.');
                    break;
                }
                $codes[$code] = true;

                $maxUses = $row['max_uses'] ?? null;
                if ($maxUses !== null && $maxUses !== '' && (int) $maxUses < 1) {
                    $validator->errors()->add('coupons.'.$idx.'.max_uses', 'Max uses must be at least 1 or left empty for unlimited.');
                }
            }
        });
    }
}
