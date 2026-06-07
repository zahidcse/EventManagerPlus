<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Http\Request;

final class PublicBookingPayload
{
    /**
     * @return array{
     *   attendee_name: string,
     *   email: string,
     *   phone: ?string,
     *   user_id: ?int,
     *   day_carts: list<array{date: ?string, qty: array<string, int>, addon_qty: array<string, int>}>,
     *   qty: array<string, int>,
     *   addon_qty: array<string, int>,
     *   occurrence_dates: list<string>,
     *   attendee_entries: list<array<string, string>>
     * }
     */
    public static function fromRequest(Event $event, Request $request, array $validated): array
    {
        $dayCarts = BookingDayCart::dayCartsFromRequest($event, $request);

        $qty = [];
        $addonQty = [];
        foreach ($dayCarts as $c) {
            foreach ($c['qty'] as $id => $n) {
                $n = (int) $n;
                if ($n > 0) {
                    $id = (string) $id;
                    $qty[$id] = ($qty[$id] ?? 0) + $n;
                }
            }
            foreach ($c['addon_qty'] as $id => $n) {
                $n = (int) $n;
                if ($n > 0) {
                    $id = (string) $id;
                    $addonQty[$id] = ($addonQty[$id] ?? 0) + $n;
                }
            }
        }

        $actor = $request->user();

        $occurrenceDates = BookingDayCart::sessionDatesWithTicketsFromPayload($event, ['day_carts' => $dayCarts]);
        $attendeeSettings = $event->attendeeSettingsResolved();
        $enabledFields = [];
        foreach (($attendeeSettings['fields'] ?? []) as $fieldKey => $isEnabled) {
            if (filter_var($isEnabled, FILTER_VALIDATE_BOOLEAN)) {
                $enabledFields[] = (string) $fieldKey;
            }
        }
        $attendeeEntries = [];
        if (filter_var($attendeeSettings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) && $enabledFields !== []) {
            $rawRows = $request->input('attendee_entries', []);
            if (is_array($rawRows)) {
                foreach ($rawRows as $rawRow) {
                    if (! is_array($rawRow)) {
                        continue;
                    }
                    $row = [];
                    foreach ($enabledFields as $fieldKey) {
                        $row[$fieldKey] = trim((string) ($rawRow[$fieldKey] ?? ''));
                    }
                    $attendeeEntries[] = $row;
                }
            }
        }

        return [
            'attendee_name' => $validated['attendee_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'user_id' => $actor !== null ? (int) $actor->getAuthIdentifier() : null,
            'day_carts' => $dayCarts,
            'qty' => $qty,
            'addon_qty' => $addonQty,
            'occurrence_dates' => $occurrenceDates,
            'attendee_entries' => $attendeeEntries,
        ];
    }

    /**
     * @param  array{date: ?string, qty: array<string, int>, addon_qty: array<string, int>}  $dayCart
     */
    public static function notesForDayCart(Event $event, array $dayCart): ?string
    {
        $parts = [];
        $d = $dayCart['date'] ?? null;
        if (is_string($d) && $d !== '') {
            try {
                $parts[] = 'Session: '.\Illuminate\Support\Carbon::parse($d)->format('M j, Y');
            } catch (\Throwable) {
                $parts[] = 'Session: '.$d;
            }
        }

        $addonParts = [];
        foreach ($event->additionalServices as $svc) {
            $n = (int) ($dayCart['addon_qty'][(string) $svc->id] ?? 0);
            if ($n > 0) {
                $addonParts[] = $svc->name.' ×'.$n;
            }
        }
        if ($addonParts !== []) {
            $parts[] = 'Add-ons: '.implode('; ', $addonParts);
        }

        return $parts !== [] ? implode("\n", $parts) : null;
    }

    public static function notesFromPayload(Event $event, array $payload): ?string
    {
        $parts = [];
        foreach (BookingDayCart::dayCartsFromPayload($event, $payload) as $cart) {
            if (array_sum($cart['qty']) === 0 && array_sum($cart['addon_qty']) === 0) {
                continue;
            }
            $dayBits = [];
            $d = $cart['date'] ?? null;
            if (is_string($d) && $d !== '') {
                try {
                    $dayBits[] = \Illuminate\Support\Carbon::parse($d)->format('D, M j, Y');
                } catch (\Throwable) {
                    $dayBits[] = $d;
                }
            }
            $ticketBits = [];
            foreach ($event->tickets as $t) {
                $n = (int) ($cart['qty'][(string) $t->id] ?? 0);
                if ($n > 0) {
                    $ticketBits[] = $t->name.' ×'.$n;
                }
            }
            $addonBits = [];
            foreach ($event->additionalServices as $svc) {
                $n = (int) ($cart['addon_qty'][(string) $svc->id] ?? 0);
                if ($n > 0) {
                    $addonBits[] = $svc->name.' ×'.$n;
                }
            }
            $line = '';
            if ($dateLabel = $dayBits[0] ?? '') {
                $line = $dateLabel.': ';
            }
            if ($ticketBits !== []) {
                $line .= 'Tickets: '.implode(', ', $ticketBits);
            }
            if ($addonBits !== []) {
                $line .= ($ticketBits !== [] ? ' · ' : '').'Add-ons: '.implode(', ', $addonBits);
            }
            if ($line !== '') {
                $parts[] = trim($line);
            }
        }

        return $parts !== [] ? implode("\n", $parts) : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function validateInventory(Event $event, array $payload, bool $staffRegistration = false): ?string
    {
        $scheduleType = $event->schedule_type ?? 'single';
        $totalSeats = BookingDayCart::totalTicketSeatCount($event, $payload);

        if ($scheduleType !== 'single' && $totalSeats === 0) {
            return 'Select at least one ticket for at least one session day.';
        }

        if ($scheduleType === 'single' && $totalSeats === 0) {
            return 'Select at least one ticket.';
        }

        $ticketRows = $event->tickets()->orderBy('sort_order')->get();
        foreach (BookingDayCart::dayCartsFromPayload($event, $payload) as $cart) {
            $dayDate = isset($cart['date']) && is_string($cart['date']) ? $cart['date'] : null;
            $dayTickets = array_sum($cart['qty']);
            $dayAddons = array_sum($cart['addon_qty']);
            if ($dayAddons > 0 && $dayTickets === 0) {
                return 'Add-ons for a session day require at least one ticket for that same day.';
            }

            foreach ($ticketRows as $ticket) {
                $qty = (int) ($cart['qty'][(string) $ticket->id] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                if (! $staffRegistration && ! $ticket->isBookableNow($dayDate)) {
                    return 'A ticket tier is no longer available.';
                }

                if (! $event->usesGlobalTicketQuantity()) {
                    $remaining = $ticket->remainingForSale($dayDate);
                    if ($remaining !== null && $qty > $remaining) {
                        return 'Not enough tickets left for one or more tiers.';
                    }
                }
            }

            if ($event->usesGlobalTicketQuantity()) {
                $pool = $event->remainingGlobalTicketPool($dayDate);
                if ($pool !== null && $dayTickets > $pool) {
                    return 'Not enough tickets left in the shared inventory pool.';
                }
            }

            if ($event->capacity > 0 && $dayTickets > 0) {
                $current = $event->bookingsCountForInventory($dayDate);
                if ($current + $dayTickets > $event->capacity) {
                    return 'This event has reached its capacity.';
                }
            }
        }

        if (! $staffRegistration && $event->max_tickets_per_customer > 0 && $totalSeats > $event->max_tickets_per_customer) {
            return 'You can purchase at most '.$event->max_tickets_per_customer.' ticket seat(s) for this event.';
        }

        $addonError = AdditionalServiceInventory::validate($event, $payload);
        if ($addonError !== null) {
            return $addonError;
        }

        return null;
    }
}
