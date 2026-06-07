<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Event;
use App\Models\EventBooking;
use App\Models\EventBookingCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class BookingThankYouSession
{
    private const SESSION_KEY = 'booking_thank_you';

    /**
     * @param  Collection<int, EventBooking>  $bookings
     */
    public static function store(Request $request, Event $event, Collection $bookings): void
    {
        if ($bookings->isEmpty()) {
            return;
        }

        $orderGroupIds = $bookings
            ->pluck('order_group_id')
            ->filter(static fn ($id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values()
            ->all();

        $bookingIds = $bookings
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();

        $request->session()->put(self::SESSION_KEY, [
            'event_id' => (int) $event->id,
            'order_group_ids' => $orderGroupIds,
            'booking_ids' => $bookingIds,
        ]);
    }

    /**
     * @param  Collection<int, EventBooking>  $bookings
     */
    public static function redirect(
        Request $request,
        Event $event,
        Collection $bookings,
        string $message,
        bool $accountReady = false,
    ): RedirectResponse {
        if ($bookings->isEmpty()) {
            $redirect = redirect()->route('events.show', $event);

            return $accountReady
                ? $redirect->with('booked', $message)->with('booked_account_ready', true)
                : $redirect->with('booked', $message);
        }

        self::store($request, $event, $bookings);

        $redirect = redirect()->route('events.booking.thank-you', $event);

        return $redirect
            ->with('booked', $message)
            ->with('booked_account_ready', $accountReady);
    }

    /**
     * @return list<string>
     */
    public static function orderGroupIdsForEvent(Request $request, Event $event): array
    {
        $data = $request->session()->get(self::SESSION_KEY);
        if (! is_array($data) || (int) ($data['event_id'] ?? 0) !== (int) $event->id) {
            return [];
        }

        $ids = $data['order_group_ids'] ?? [];

        return is_array($ids)
            ? array_values(array_filter($ids, static fn ($id): bool => is_string($id) && $id !== ''))
            : [];
    }

    public static function canAccessOrder(Request $request, Event $event, string $orderGroupId): bool
    {
        if ($orderGroupId === '') {
            return false;
        }

        if (self::orderGroupIdsForEvent($request, $event) !== []) {
            return in_array($orderGroupId, self::orderGroupIdsForEvent($request, $event), true);
        }

        $user = $request->user();
        if ($user === null) {
            return false;
        }

        return EventBooking::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->where('order_group_id', $orderGroupId)
            ->exists();
    }

    public static function canAccessBooking(Request $request, EventBooking $booking): bool
    {
        $user = $request->user();
        if ($user !== null && (int) $booking->user_id === (int) $user->id) {
            return true;
        }

        $data = $request->session()->get(self::SESSION_KEY);
        if (! is_array($data)) {
            return false;
        }

        if ((int) ($data['event_id'] ?? 0) !== (int) $booking->event_id) {
            return false;
        }

        $bookingIds = $data['booking_ids'] ?? [];
        if (! is_array($bookingIds)) {
            return false;
        }

        return in_array((int) $booking->id, array_map('intval', $bookingIds), true);
    }

    /**
     * @return Collection<int, EventBooking>
     */
    public static function bookingsForCheckout(EventBookingCheckout $checkout): Collection
    {
        $checkout->refresh();

        $base = EventBooking::query()
            ->where('event_id', $checkout->event_id)
            ->with(['event', 'ticket']);

        if ($checkout->stripe_checkout_session_id) {
            $bookings = (clone $base)
                ->where('stripe_checkout_session_id', $checkout->stripe_checkout_session_id)
                ->orderBy('id')
                ->get();
            if ($bookings->isNotEmpty()) {
                return $bookings;
            }
        }

        if ($checkout->paypal_order_id) {
            $bookings = (clone $base)
                ->where('paypal_order_id', $checkout->paypal_order_id)
                ->orderBy('id')
                ->get();
            if ($bookings->isNotEmpty()) {
                return $bookings;
            }
        }

        return self::bookingsForCheckoutByPayloadWindow($checkout);
    }

    /**
     * Razorpay and SSLCommerz store different refs on checkout vs bookings; match by email and paid time.
     *
     * @return Collection<int, EventBooking>
     */
    private static function bookingsForCheckoutByPayloadWindow(EventBookingCheckout $checkout): Collection
    {
        if ($checkout->paid_at === null) {
            return collect();
        }

        $payload = is_array($checkout->payload) ? $checkout->payload : [];
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        if ($email === '') {
            return collect();
        }

        $paidAt = $checkout->paid_at;

        return EventBooking::query()
            ->where('event_id', $checkout->event_id)
            ->where('email', $email)
            ->whereBetween('created_at', [
                $paidAt->copy()->subMinute(),
                $paidAt->copy()->addMinute(),
            ])
            ->with(['event', 'ticket'])
            ->orderBy('id')
            ->get();
    }
}
