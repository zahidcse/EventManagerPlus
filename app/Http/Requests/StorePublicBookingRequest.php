<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\EventTicket;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdditionalServiceInventory;
use App\Support\BookingDayCart;
use App\Support\BookingOnlineRedirect;
use App\Support\BookingOrderTotals;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StorePublicBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'attendee_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'qty_by_date' => ['nullable', 'array'],
            'qty_by_date.*' => ['nullable', 'array'],
            'qty_by_date.*.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'addon_qty_by_date' => ['nullable', 'array'],
            'addon_qty_by_date.*' => ['nullable', 'array'],
            'addon_qty_by_date.*.*' => ['nullable', 'integer', 'min:0', 'max:50'],
            'payment_method' => ['nullable', 'string', Rule::in(['stripe', 'paypal', 'razorpay', 'sslcommerz', 'cash', 'bank_transfer'])],
            'offline_payment_reference' => ['nullable', 'string', 'max:191'],
            'attendee_entries' => ['nullable', 'array'],
            'attendee_entries.*' => ['nullable', 'array'],
            'attendee_entries.*.name' => ['nullable', 'string', 'max:255'],
            'attendee_entries.*.email' => ['nullable', 'email', 'max:255'],
            'attendee_entries.*.phone' => ['nullable', 'string', 'max:64'],
            'attendee_entries.*.gender' => ['nullable', 'string', 'max:64'],
            'attendee_entries.*.driving_license' => ['nullable', 'string', 'max:191'],
            'attendee_entries.*.nid' => ['nullable', 'string', 'max:191'],
            'attendee_entries.*.location' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->user() !== null) {
            return $rules;
        }

        $guestWantsAccount = $this->boolean('create_account');

        $rules['create_account'] = ['sometimes', 'boolean'];

        $wantProfile = fn (): bool => $guestWantsAccount;

        $rules['password'] = [
            Rule::excludeUnless($wantProfile),
            Password::defaults(),
            'confirmed',
        ];

        $rules['password_confirmation'] = [
            Rule::excludeUnless($wantProfile),
        ];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $event = $this->route('event');
        if (! $event instanceof Event) {
            return;
        }

        $event->loadMissing([
            'tickets' => fn ($q) => $q->orderBy('sort_order'),
            'additionalServices' => fn ($q) => $q->orderBy('sort_order'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $event = $this->route('event');
            if (! $event instanceof Event) {
                return;
            }

            $event->loadMissing([
                'tickets' => fn ($q) => $q->orderBy('sort_order'),
                'additionalServices' => fn ($q) => $q->orderBy('sort_order'),
            ]);

            if ($event->visibility !== 'public' || $event->status !== 'active') {
                $v->errors()->add('event', 'This event is not available for booking.');

                return;
            }

            $hasTicketQtyError = false;
            $seatRows = 0;

            if (BookingDayCart::usesPerDayCart($event)) {
                $choices = $event->bookableOccurrenceDateStrings();
                if ($choices === []) {
                    $v->errors()->add('qty', 'There are no upcoming session dates available for booking.');
                }

                $dayTicketSums = [];
                foreach ($choices as $d) {
                    $dayTicketSums[$d] = 0;
                }

                foreach ($choices as $d) {
                    foreach ($event->tickets as $ticket) {
                        /** @var EventTicket $ticket */
                        $q = (int) $this->input('qty_by_date.'.$d.'.'.$ticket->id, 0);
                        if ($q < 0 || $q > 100) {
                            $hasTicketQtyError = true;
                            $v->errors()->add('qty_by_date.'.$d.'.'.$ticket->id, 'Choose a valid quantity.');

                            continue;
                        }
                        if ($q > 0) {
                            if (! $ticket->isBookableNow($d)) {
                                $hasTicketQtyError = true;
                                $v->errors()->add('qty_by_date.'.$d.'.'.$ticket->id, 'This ticket tier is not available.');

                                continue;
                            }

                            if (! $event->usesGlobalTicketQuantity()) {
                                $remaining = $ticket->remainingForSale($d);
                                if ($remaining !== null && $q > $remaining) {
                                    $hasTicketQtyError = true;
                                    $v->errors()->add('qty_by_date.'.$d.'.'.$ticket->id, 'Not enough tickets left for this day.');

                                    continue;
                                }
                            }

                            $dayTicketSums[$d] += $q;
                        }
                    }

                    foreach ($event->additionalServices as $svc) {
                        $aq = (int) $this->input('addon_qty_by_date.'.$d.'.'.$svc->id, 0);
                        if ($aq < 0) {
                            $v->errors()->add('addon_qty_by_date.'.$d.'.'.$svc->id, 'Invalid quantity for add-on.');

                            continue;
                        }
                        $remaining = $svc->remainingForSale();
                        $max = $remaining !== null ? min(50, $remaining) : 50;
                        if ($aq > $max) {
                            $v->errors()->add('addon_qty_by_date.'.$d.'.'.$svc->id, $remaining === 0
                                ? '"'.$svc->name.'" is sold out.'
                                : 'Not enough "'.$svc->name.'" left in stock.');
                        }
                    }

                    $dayTicketSum = (int) ($dayTicketSums[$d] ?? 0);
                    $dayAddonSum = 0;
                    foreach ($event->additionalServices as $svc) {
                        $dayAddonSum += (int) $this->input('addon_qty_by_date.'.$d.'.'.$svc->id, 0);
                    }
                    if ($dayAddonSum > 0 && $dayTicketSum === 0) {
                        $v->errors()->add('qty_by_date.'.$d, 'Add-ons for '. $d.' require at least one ticket for that day.');
                    }

                    if ($event->usesGlobalTicketQuantity()) {
                        $pool = $event->remainingGlobalTicketPool($d);
                        if ($pool !== null && $dayTicketSum > $pool) {
                            $hasTicketQtyError = true;
                            $v->errors()->add('qty_by_date.'.$d, 'Not enough tickets left in the shared inventory pool for this day.');
                        }
                    }

                    if ($event->capacity > 0 && $dayTicketSum > 0) {
                        $currentForDay = $event->bookingsCountForInventory($d);
                        if ($currentForDay + $dayTicketSum > $event->capacity) {
                            $v->errors()->add('qty_by_date.'.$d, 'This day has reached its capacity.');
                        }
                    }
                }

                $totalQty = array_sum($dayTicketSums);

                if ($totalQty === 0 && ! $hasTicketQtyError && $choices !== []) {
                    $v->errors()->add('qty', 'Select at least one ticket for at least one session day.');
                }

                $seatRows = $totalQty;

                if ($event->max_tickets_per_customer > 0 && $seatRows > $event->max_tickets_per_customer) {
                    $v->errors()->add('qty', 'You can purchase at most '.$event->max_tickets_per_customer.' ticket seat(s) for this event.');
                }
            } else {
                $totalQty = 0;

                foreach ($event->tickets()->orderBy('sort_order')->get() as $ticket) {
                    /** @var EventTicket $ticket */
                    $q = (int) $this->input('qty.'.$ticket->id, 0);
                    if ($q < 0 || $q > 100) {
                        $hasTicketQtyError = true;
                        $v->errors()->add('qty.'.$ticket->id, 'Choose a valid quantity.');

                        continue;
                    }
                    if ($q === 0) {
                        continue;
                    }
                    if (! $ticket->isBookableNow()) {
                        $hasTicketQtyError = true;
                        $v->errors()->add('qty.'.$ticket->id, 'This ticket tier is not available.');

                        continue;
                    }
                    $remaining = $ticket->remainingForSale();
                    $skipTierRemainingCap = $event->usesGlobalTicketQuantity()
                        && (int) $event->global_ticket_quantity > 0;
                    if (! $skipTierRemainingCap && $remaining !== null && $q > $remaining) {
                        $hasTicketQtyError = true;
                        $v->errors()->add('qty.'.$ticket->id, 'Not enough tickets left for this tier.');

                        continue;
                    }
                    $totalQty += $q;
                }

                if ($totalQty === 0 && ! $hasTicketQtyError) {
                    $v->errors()->add('qty', 'Select at least one ticket.');
                }

                if ($event->max_tickets_per_customer > 0 && $totalQty > $event->max_tickets_per_customer) {
                    $v->errors()->add('qty', 'You can purchase at most '.$event->max_tickets_per_customer.' ticket(s) for this event.');
                }

                if ($event->capacity > 0) {
                    $current = $event->bookings()->count();
                    if ($current + $totalQty > $event->capacity) {
                        $v->errors()->add('qty', 'This event has reached its capacity.');
                    }
                }
                $seatRows = $totalQty;

                foreach ($event->additionalServices()->orderBy('sort_order')->get() as $svc) {
                    $aq = (int) $this->input('addon_qty.'.$svc->id, 0);
                    if ($aq < 0) {
                        $v->errors()->add('addon_qty.'.$svc->id, 'Invalid quantity for add-on.');

                        continue;
                    }
                    $remaining = $svc->remainingForSale();
                    $max = $remaining !== null ? min(50, $remaining) : 50;
                    if ($aq > $max) {
                        $v->errors()->add('addon_qty.'.$svc->id, $remaining === 0
                            ? '"'.$svc->name.'" is sold out.'
                            : 'Not enough "'.$svc->name.'" left in stock.');
                    }
                }
            }

            $addonPayload = ['day_carts' => BookingDayCart::dayCartsFromRequest($event, $this)];
            $addonAggregateError = AdditionalServiceInventory::validate($event, $addonPayload);
            if ($addonAggregateError !== null && ! $v->errors()->has('addon_qty') && ! $v->errors()->has('addon_qty_by_date')) {
                $v->errors()->add('addon_qty', $addonAggregateError);
            }

            $attendeeSettings = $event->attendeeSettingsResolved();
            $collectPerTicket = filter_var($attendeeSettings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($collectPerTicket && $seatRows > 0) {
                $entries = $this->input('attendee_entries', []);
                if (! is_array($entries)) {
                    $v->errors()->add('attendee_entries', 'Enter attendee details for each ticket.');
                    $entries = [];
                }

                if (count($entries) < $seatRows) {
                    $v->errors()->add('attendee_entries', 'Enter attendee details for each ticket seat you selected.');
                }

                $fieldDefinitions = Event::attendeeFieldDefinitions();
                $requiredFields = [];
                foreach (($attendeeSettings['fields'] ?? []) as $field => $enabled) {
                    if (filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
                        $requiredFields[] = (string) $field;
                    }
                }

                for ($i = 0; $i < $seatRows; $i++) {
                    $row = $entries[$i] ?? null;
                    if (! is_array($row)) {
                        $v->errors()->add('attendee_entries.'.$i, 'Attendee #'.($i + 1).' details are missing.');

                        continue;
                    }
                    foreach ($requiredFields as $fieldKey) {
                        $value = trim((string) ($row[$fieldKey] ?? ''));
                        $label = strtolower((string) ($fieldDefinitions[$fieldKey]['label'] ?? $fieldKey));
                        if ($value === '') {
                            $v->errors()->add(
                                'attendee_entries.'.$i.'.'.$fieldKey,
                                'Attendee #'.($i + 1).' '.$label.' is required.'
                            );

                            continue;
                        }
                        if ($fieldKey === 'email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $v->errors()->add(
                                'attendee_entries.'.$i.'.'.$fieldKey,
                                'Attendee #'.($i + 1).' email must be a valid email address.'
                            );
                        }
                    }
                }
            }

            if ($this->user() === null && $this->boolean('create_account')) {
                $emailKey = strtolower(trim((string) $this->input('email', '')));
                if ($emailKey !== '') {
                    $exists = User::query()->whereRaw('LOWER(email) = ?', [$emailKey])->exists();
                    if ($exists) {
                        $v->errors()->add('email', 'An account already exists with this email. Sign in instead.');
                    }
                }
            }

            $setting = Schema::hasTable('site_settings') ? SiteSetting::query()->first() : null;

            $totals = BookingOrderTotals::fromEventAndRequest($event, $this);

            $payable = $totals->payableTotalCents > 0;

            if (! $payable) {
                return;
            }

            $gates = BookingOnlineRedirect::gatewaysReady($totals, $setting);
            $stripeReady = $gates['stripe'];
            $paypalReady = $gates['paypal'];
            $razorpayReady = $gates['razorpay'];
            $sslCommerzReady = $gates['sslcommerz'];
            $cashReady = ($setting?->payment_cash_enabled ?? false);
            $bankReady = ($setting?->payment_bank_transfer_enabled ?? false);

            /** @var list<string> $allowed */
            $allowed = array_values(array_filter([
                $stripeReady ? 'stripe' : null,
                $paypalReady ? 'paypal' : null,
                $razorpayReady ? 'razorpay' : null,
                $sslCommerzReady ? 'sslcommerz' : null,
                $cashReady ? 'cash' : null,
                $bankReady ? 'bank_transfer' : null,
            ]));

            if ($allowed === []) {
                $v->errors()->add('payment_method', 'Online booking is unavailable: no payment method is configured.');

                return;
            }

            $method = (string) $this->input('payment_method', '');

            if (! in_array($method, $allowed, true)) {
                $v->errors()->add('payment_method', 'Choose a valid payment option for your order.');
            }

            if (in_array($method, ['cash', 'bank_transfer'], true)) {
                $ref = trim((string) $this->input('offline_payment_reference', ''));
                if ($ref === '') {
                    $v->errors()->add('offline_payment_reference', 'Enter your payment or transfer reference.');
                }
            }
        });
    }
}
