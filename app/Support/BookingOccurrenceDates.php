<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use Illuminate\Http\Request;

/**
 * Normalizes multi-session date selection from HTTP requests and payloads.
 */
final class BookingOccurrenceDates
{
    /**
     * @return list<string>
     */
    public static function fromRequest(Event $event, Request $request): array
    {
        if (($event->schedule_type ?? 'single') === 'single') {
            return [];
        }

        return BookingDayCart::sessionDatesWithTicketsFromPayload($event, [
            'day_carts' => BookingDayCart::dayCartsFromRequest($event, $request),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload  Public booking payload with day_carts / legacy fields
     * @return list<string>
     */
    public static function fromPayload(Event $event, array $payload): array
    {
        if (($event->schedule_type ?? 'single') === 'single') {
            return [];
        }

        return BookingDayCart::sessionDatesWithTicketsFromPayload($event, $payload);
    }

    /**
     * @deprecated  Prefer BookingDayCart::totalTicketSeatCount or day-level validation
     */
    public static function sessionCountForRequest(Event $event, Request $request): int
    {
        if (($event->schedule_type ?? 'single') === 'single') {
            return 1;
        }

        return max(1, count(BookingDayCart::activeDayCartsForPayload($event, [
            'day_carts' => BookingDayCart::dayCartsFromRequest($event, $request),
        ])));
    }

    /**
     * @deprecated  Prefer BookingDayCart::totalTicketSeatCount
     *
     * @param  array<string, mixed>  $payload
     */
    public static function sessionCountForPayload(Event $event, array $payload): int
    {
        if (($event->schedule_type ?? 'single') === 'single') {
            return 1;
        }

        return max(0, count(BookingDayCart::activeDayCartsForPayload($event, $payload)));
    }

    /**
     * @return list<?string>  Y-m-d strings, or [null] for single-schedule events
     *
     * @param  array<string, mixed>  $payload
     */
    public static function datesForCreatingBookings(Event $event, array $payload): array
    {
        if (($event->schedule_type ?? 'single') === 'single') {
            return [null];
        }
        $dates = self::fromPayload($event, $payload);
        $dates = array_values(array_unique($dates));
        sort($dates, SORT_STRING);

        return $dates === [] ? [] : $dates;
    }
}
