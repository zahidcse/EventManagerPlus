<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Event;

use App\Models\Event;
use App\Models\User;
use App\Support\BookingDayCart;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAdminEventRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_kind' => ['required', 'string', Rule::in(['guest', 'registered_user'])],
            'user_id' => [
                'nullable',
                'integer',
                'required_if:registration_kind,registered_user',
                Rule::exists('users', 'id')->where(static fn ($q) => $q->where('is_admin', false)),
            ],
            'guest_name' => ['nullable', 'string', 'max:255', 'required_if:registration_kind,guest'],
            'guest_email' => ['nullable', 'email', 'max:255', 'required_if:registration_kind,guest'],
            'phone' => ['nullable', 'string', 'max:64'],
            'admin_qty' => ['nullable', 'array'],
            'admin_qty.*' => ['integer', 'min:0', 'max:100'],
            'admin_qty_by_date' => ['nullable', 'array'],
            'admin_qty_by_date.*' => ['nullable', 'array'],
            'admin_qty_by_date.*.*' => ['nullable', 'integer', 'min:0', 'max:100'],
            'admin_addon_qty' => ['nullable', 'array'],
            'admin_addon_qty.*' => ['integer', 'min:0', 'max:50'],
            'admin_addon_qty_by_date' => ['nullable', 'array'],
            'admin_addon_qty_by_date.*' => ['nullable', 'array'],
            'admin_addon_qty_by_date.*.*' => ['nullable', 'integer', 'min:0', 'max:50'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'send_confirmation' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'send_confirmation' => $this->boolean('send_confirmation'),
        ]);

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

            $ticketIds = $event->tickets()->pluck('id')->map(fn ($id) => (string) $id)->all();

            if (BookingDayCart::usesPerDayCart($event)) {
                $choices = $event->occurrenceDateStringsForStaffRegistration();
                if ($choices === []) {
                    $v->errors()->add('admin_qty', 'This event has no session dates configured.');
                }

                $ticketSums = [];
                foreach ($ticketIds as $tid) {
                    $ticketSums[$tid] = 0;
                }
                $dayTicketSums = [];
                foreach ($choices as $d) {
                    $dayTicketSums[$d] = 0;
                }

                foreach ($choices as $d) {
                    foreach ($event->tickets as $ticket) {
                        $q = (int) $this->input('admin_qty_by_date.'.$d.'.'.$ticket->id, 0);
                        if ($q < 0 || $q > 100) {
                            $v->errors()->add('admin_qty_by_date.'.$d.'.'.$ticket->id, 'Choose a valid quantity.');
                        }
                        $ticketSums[(string) $ticket->id] += $q;
                        $dayTicketSums[$d] += max(0, $q);
                    }
                    foreach ($event->additionalServices as $svc) {
                        $aq = (int) $this->input('admin_addon_qty_by_date.'.$d.'.'.$svc->id, 0);
                        if ($aq < 0 || $aq > 50) {
                            $v->errors()->add('admin_addon_qty_by_date.'.$d.'.'.$svc->id, 'Invalid add-on quantity.');
                        }
                    }
                    $dayTicketSum = 0;
                    foreach ($event->tickets as $ticket) {
                        $dayTicketSum += (int) $this->input('admin_qty_by_date.'.$d.'.'.$ticket->id, 0);
                    }
                    $dayAddonSum = 0;
                    foreach ($event->additionalServices as $svc) {
                        $dayAddonSum += (int) $this->input('admin_addon_qty_by_date.'.$d.'.'.$svc->id, 0);
                    }
                    if ($dayAddonSum > 0 && $dayTicketSum === 0) {
                        $v->errors()->add('admin_qty_by_date.'.$d, 'Add-ons for this day require at least one ticket for the same day.');
                    }
                }

                $total = array_sum($ticketSums);
                if ($total <= 0) {
                    $v->errors()->add('admin_qty', 'Enter at least one ticket (quantity greater than zero) for at least one session day.');
                }

                $seatRows = $total;
                if ($event->capacity > 0 && $seatRows > 0) {
                    foreach ($dayTicketSums as $d => $dayTicketSum) {
                        if ($dayTicketSum <= 0) {
                            continue;
                        }
                        $currentForDay = $event->bookingsCountForInventory((string) $d);
                        if ($currentForDay + $dayTicketSum > $event->capacity) {
                            $v->errors()->add('admin_qty_by_date.'.$d, 'This registration would exceed capacity for this day.');
                        }
                    }
                }
            } else {
                /** @var array<string, mixed> $adminQty */
                $adminQty = $this->input('admin_qty', []);
                if (! is_array($adminQty)) {
                    $v->errors()->add('admin_qty', 'Select ticket quantities.');

                    return;
                }

                foreach (array_keys($adminQty) as $key) {
                    if (! is_string($key) && ! is_int($key)) {
                        continue;
                    }
                    $k = (string) $key;
                    if (! in_array($k, $ticketIds, true)) {
                        $v->errors()->add('admin_qty', 'Invalid ticket tier in this request.');
                    }
                }

                $total = 0;
                foreach ($ticketIds as $tid) {
                    $total += max(0, (int) ($adminQty[$tid] ?? 0));
                }
                if ($total <= 0) {
                    $v->errors()->add('admin_qty', 'Enter at least one ticket (quantity greater than zero).');
                }

                /** @var array<string, mixed> $addonRaw */
                $addonRaw = $this->input('admin_addon_qty', []);
                $addonRaw = is_array($addonRaw) ? $addonRaw : [];
                $addonIds = $event->additionalServices()->pluck('id')->map(fn ($id) => (string) $id)->all();
                foreach (array_keys($addonRaw) as $key) {
                    $k = (string) $key;
                    if (! in_array($k, $addonIds, true)) {
                        $v->errors()->add('admin_addon_qty', 'Invalid add-on in this request.');
                    }
                }

                $seatRows = $total;
                if ($event->capacity > 0 && $seatRows > 0) {
                    $current = $event->bookings()->count();
                    if ($current + $seatRows > $event->capacity) {
                        $v->errors()->add('admin_qty', 'This registration would exceed the event capacity.');
                    }
                }
            }
        });
    }

    /** @param  User|null  $user  Resolved customer user when registration_kind is registered_user */
    public function attendeePayload(?User $user): array
    {
        $kind = (string) $this->input('registration_kind');
        if ($kind === 'registered_user' && $user !== null) {
            return [
                'attendee_name' => (string) $user->name,
                'email' => strtolower(trim((string) $user->email)),
                'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            ];
        }

        return [
            'attendee_name' => (string) $this->input('guest_name', ''),
            'email' => strtolower(trim((string) $this->input('guest_email', ''))),
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
        ];
    }
}
