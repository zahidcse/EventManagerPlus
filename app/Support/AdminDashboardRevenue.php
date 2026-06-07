<?php



namespace App\Support;



use App\Models\Event;

use App\Models\EventBooking;

use App\Models\EventBookingCheckout;

use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Carbon;

use Illuminate\Support\Collection;

use Illuminate\Support\Facades\Schema;



final class AdminDashboardRevenue

{

    /**

     * Revenue for classic reports using the same rules as the dashboard, optionally scoped by report filters.

     *

     * @param  array{

     *     date_from?: ?string,

     *     date_to?: ?string,

     *     event_id?: ?int,

     *     organizer_id?: ?int,

     *     check_in?: ?string,

     *     location?: ?string,

     *     status?: ?string,

     * }  $filters

     * @return array{

     *     available: bool,

     *     currency: string,

     *     total_cents: int,

     *     online_cents: int,

     *     offline_cents: int,

     *     formatted: string,

     * }

     */

    public static function revenueForFilters(array $filters = []): array

    {

        if (! Schema::hasTable('event_booking_checkouts')) {

            return [

                'available' => false,

                'currency' => 'usd',

                'total_cents' => 0,

                'online_cents' => 0,

                'offline_cents' => 0,

                'formatted' => self::formatCents(0, 'usd'),

            ];

        }



        $paidCheckouts = self::paidCheckouts();

        $filteredOnline = self::filterPaidCheckouts($paidCheckouts, $filters);

        $onlineCents = (int) $filteredOnline->sum('amount_total_cents');



        $offlineBookings = self::offlineBookingsQuery($filters)

            ->with(['ticket' => fn ($q) => $q->select('id', 'price', 'early_bird_price', 'early_bird_ends_at')])

            ->get(['id', 'event_ticket_id', 'offline_payment_method', 'created_at', 'status', 'checked_in_at']);



        $offlineCents = self::sumOfflineBookingCents($offlineBookings);



        $currency = strtolower((string) ($filteredOnline->sortByDesc('paid_at')->first()?->currency

            ?? $paidCheckouts->sortByDesc('paid_at')->first()?->currency

            ?? 'usd'));



        $totalCents = $onlineCents + $offlineCents;



        return [

            'available' => true,

            'currency' => $currency,

            'total_cents' => $totalCents,

            'online_cents' => $onlineCents,

            'offline_cents' => $offlineCents,

            'formatted' => self::formatCents($totalCents, $currency),

        ];

    }



    /**

     * @return array{

     *     available: bool,

     *     currency: string,

     *     total_cents: int,

     *     this_month_cents: int,

     *     online_cents: int,

     *     offline_cents: int,

     *     paid_orders: int,

     *     breakdown: list<array{key: string, label: string, cents: int, count: int, color: string}>,

     *     chart_days: list<array{label: string, date: string, cents: int, height_percent: int}>,

     *     top_events: list<array{event_id: int, title: string, cents: int, orders: int}>

     * }

     */

    public static function snapshot(): array

    {

        if (! Schema::hasTable('event_booking_checkouts')) {

            return self::emptySnapshot();

        }



        $now = Carbon::now();

        $monthStart = $now->copy()->startOfMonth();



        $paidCheckouts = self::paidCheckouts();



        $onlineCents = (int) $paidCheckouts->sum('amount_total_cents');

        $thisMonthCents = (int) $paidCheckouts

            ->filter(fn (EventBookingCheckout $c) => $c->paid_at !== null && $c->paid_at->gte($monthStart))

            ->sum('amount_total_cents');



        $currency = strtolower((string) ($paidCheckouts->sortByDesc('paid_at')->first()?->currency ?? 'usd'));



        $channelDefs = [

            'stripe' => ['label' => 'Stripe', 'color' => 'bg-indigo-500'],

            'paypal' => ['label' => 'PayPal', 'color' => 'bg-sky-500'],

            'razorpay' => ['label' => 'Razorpay', 'color' => 'bg-blue-600'],

            'sslcommerz' => ['label' => 'SSLCommerz', 'color' => 'bg-violet-500'],

            'other_online' => ['label' => 'Other online', 'color' => 'bg-slate-500'],

        ];



        $channelBuckets = array_fill_keys(array_keys($channelDefs), ['cents' => 0, 'count' => 0]);



        foreach ($paidCheckouts as $checkout) {

            $key = self::checkoutChannelKey($checkout);

            $channelBuckets[$key]['cents'] += (int) $checkout->amount_total_cents;

            $channelBuckets[$key]['count']++;

        }



        $offlineBuckets = self::offlineCatalogBuckets();

        $offlineCents = (int) array_sum(array_column($offlineBuckets, 'cents'));



        $breakdown = [];

        foreach ($channelDefs as $key => $meta) {

            $row = $channelBuckets[$key];

            if ($row['cents'] <= 0 && $row['count'] <= 0) {

                continue;

            }

            $breakdown[] = [

                'key' => $key,

                'label' => $meta['label'],

                'cents' => $row['cents'],

                'count' => $row['count'],

                'color' => $meta['color'],

            ];

        }



        foreach ($offlineBuckets as $row) {

            if ($row['cents'] <= 0 && $row['count'] <= 0) {

                continue;

            }

            $breakdown[] = $row;

        }



        usort($breakdown, fn (array $a, array $b) => $b['cents'] <=> $a['cents']);



        $totalCents = $onlineCents + $offlineCents;



        $chartStart = $now->copy()->subDays(6)->startOfDay();

        $chartDays = [];

        for ($i = 0; $i < 7; $i++) {

            $day = $chartStart->copy()->addDays($i);

            $dayCents = (int) $paidCheckouts

                ->filter(function (EventBookingCheckout $c) use ($day) {

                    return $c->paid_at !== null && $c->paid_at->isSameDay($day);

                })

                ->sum('amount_total_cents');

            $chartDays[] = [

                'label' => $day->format('D'),

                'date' => $day->toDateString(),

                'cents' => $dayCents,

            ];

        }

        $maxChartCents = max(1, ...array_column($chartDays, 'cents'));

        foreach ($chartDays as $idx => $row) {

            $chartDays[$idx]['height_percent'] = (int) round(($row['cents'] / $maxChartCents) * 100);

        }



        $topEvents = self::topEventsByPaidRevenue($paidCheckouts);



        return [

            'available' => true,

            'currency' => $currency,

            'total_cents' => $totalCents,

            'this_month_cents' => $thisMonthCents,

            'online_cents' => $onlineCents,

            'offline_cents' => $offlineCents,

            'paid_orders' => $paidCheckouts->count(),

            'breakdown' => $breakdown,

            'chart_days' => $chartDays,

            'top_events' => $topEvents,

        ];

    }



    public static function formatCents(int $cents, string $currency = 'usd'): string

    {

        $amount = number_format($cents / 100, 2);

        $cur = strtolower($currency);



        return match ($cur) {

            'usd' => '$'.$amount,

            'eur' => '€'.$amount,

            'gbp' => '£'.$amount,

            'bdt' => '৳'.$amount,

            default => strtoupper($cur).' '.$amount,

        };

    }



    /**

     * @return Collection<int, EventBookingCheckout>

     */

    private static function paidCheckouts(): Collection

    {

        return EventBookingCheckout::query()

            ->where('status', 'paid')

            ->get(['id', 'event_id', 'amount_total_cents', 'currency', 'paid_at', 'stripe_checkout_session_id', 'paypal_order_id', 'razorpay_order_id', 'sslcommerz_tran_id']);

    }



    /**

     * @param  Collection<int, EventBookingCheckout>  $paidCheckouts

     * @param  array<string, mixed>  $filters

     * @return Collection<int, EventBookingCheckout>

     */

    private static function filterPaidCheckouts(Collection $paidCheckouts, array $filters): Collection

    {

        if (! self::hasReportFilters($filters)) {

            return $paidCheckouts;

        }



        $eventIds = $paidCheckouts->pluck('event_id')->unique()->filter()->values();

        $eventsById = Event::query()

            ->whereIn('id', $eventIds)

            ->get(['id', 'organizer_id', 'starts_at', 'ends_at', 'venue_city', 'venue_state', 'venue_country', 'venue_street'])

            ->keyBy('id');



        $needsBookingScope = ($filters['check_in'] ?? null) !== null || ($filters['status'] ?? null) !== null;



        return $paidCheckouts->filter(function (EventBookingCheckout $checkout) use ($filters, $eventsById, $needsBookingScope) {

            $event = $eventsById->get((int) $checkout->event_id);

            if (! self::eventMatchesFilters($event, $filters)) {

                return false;

            }



            if (! $needsBookingScope) {

                return true;

            }



            $bookings = BookingThankYouSession::bookingsForCheckout($checkout);



            return $bookings->contains(fn (EventBooking $booking) => self::bookingMatchesRowFilters($booking, $filters));

        })->values();

    }



    /**

     * @param  array<string, mixed>  $filters

     * @return Builder<EventBooking>

     */

    private static function offlineBookingsQuery(array $filters): Builder

    {

        $query = EventBooking::query()

            ->whereNull('stripe_checkout_session_id')

            ->whereNull('paypal_order_id')

            ->whereNull('razorpay_payment_id')

            ->whereNull('sslcommerz_val_id');



        if ($filters['status'] ?? null) {

            $query->where('status', $filters['status']);

        } else {

            $query->whereIn('status', ['confirmed', 'checked_in', 'pending_offline_payment']);

        }



        self::applyCheckInFilterToBookings($query, $filters);



        $query->whereHas('event', function (Builder $eq) use ($filters): void {

            self::applyEventFiltersToEventQuery($eq, $filters);

        });



        return $query;

    }



    /**

     * @param  array<string, mixed>  $filters

     */

    private static function hasReportFilters(array $filters): bool

    {

        return ($filters['date_from'] ?? null) !== null

            || ($filters['date_to'] ?? null) !== null

            || ($filters['event_id'] ?? null) !== null

            || ($filters['organizer_id'] ?? null) !== null

            || ($filters['check_in'] ?? null) !== null

            || ($filters['location'] ?? null) !== null

            || ($filters['status'] ?? null) !== null;

    }



    /**

     * @param  array<string, mixed>  $filters

     */

    private static function eventMatchesFilters(?Event $event, array $filters): bool

    {

        if ($event === null) {

            return false;

        }



        if (($filters['event_id'] ?? null) && (int) $event->id !== (int) $filters['event_id']) {

            return false;

        }



        if (($filters['organizer_id'] ?? null) && (int) $event->organizer_id !== (int) $filters['organizer_id']) {

            return false;

        }



        if ($filters['location'] ?? null) {
            $needle = strtolower((string) $filters['location']);
            $matchesLocation = false;
            foreach (['venue_city', 'venue_state', 'venue_country', 'venue_street'] as $col) {
                $value = strtolower((string) ($event->{$col} ?? ''));
                if ($value !== '' && str_contains($value, $needle)) {
                    $matchesLocation = true;
                    break;
                }
            }
            if (! $matchesLocation) {
                return false;
            }
        }



        $from = $filters['date_from'] ?? null;

        $to = $filters['date_to'] ?? null;

        if ($from || $to) {

            if ($event->starts_at === null) {

                return false;

            }



            $fromStart = $from

                ? Carbon::parse($from)->startOfDay()

                : Carbon::create(1970, 1, 1);

            $toEnd = $to

                ? Carbon::parse($to)->endOfDay()

                : Carbon::now()->addYears(50);



            if ($event->starts_at->gt($toEnd)) {

                return false;

            }



            if ($event->ends_at !== null && $event->ends_at->lt($fromStart)) {

                return false;

            }

        }



        return true;

    }



    /**

     * @param  Builder<Event>  $eq

     * @param  array<string, mixed>  $filters

     */

    private static function applyEventFiltersToEventQuery(Builder $eq, array $filters): void

    {

        if ($filters['event_id'] ?? null) {

            $eq->whereKey($filters['event_id']);

        }

        if ($filters['organizer_id'] ?? null) {

            $eq->where('organizer_id', $filters['organizer_id']);

        }

        if ($filters['location'] ?? null) {

            $term = '%'.addcslashes((string) $filters['location'], '%_\\').'%';

            $eq->where(function (Builder $lq) use ($term): void {

                $lq->where('venue_city', 'like', $term)

                    ->orWhere('venue_state', 'like', $term)

                    ->orWhere('venue_country', 'like', $term)

                    ->orWhere('venue_street', 'like', $term);

            });

        }



        $from = $filters['date_from'] ?? null;

        $to = $filters['date_to'] ?? null;

        if ($from || $to) {

            $fromStart = $from

                ? Carbon::parse($from)->startOfDay()

                : Carbon::create(1970, 1, 1);

            $toEnd = $to

                ? Carbon::parse($to)->endOfDay()

                : Carbon::now()->addYears(50);



            $eq->whereNotNull('starts_at')

                ->where('starts_at', '<=', $toEnd)

                ->where(function (Builder $oq) use ($fromStart): void {

                    $oq->whereNull('ends_at')->orWhere('ends_at', '>=', $fromStart);

                });

        }

    }



    /** @param  Builder<EventBooking>  $query */

    private static function applyCheckInFilterToBookings(Builder $query, array $filters): void

    {

        if (($filters['check_in'] ?? null) === 'checked_in') {

            $query->whereNotNull('checked_in_at');



            return;

        }

        if (($filters['check_in'] ?? null) === 'not_checked_in') {

            $query->whereNull('checked_in_at');

        }

    }



    /**

     * @param  array<string, mixed>  $filters

     */

    private static function bookingMatchesRowFilters(EventBooking $booking, array $filters): bool

    {

        if (($filters['check_in'] ?? null) === 'checked_in' && $booking->checked_in_at === null) {

            return false;

        }

        if (($filters['check_in'] ?? null) === 'not_checked_in' && $booking->checked_in_at !== null) {

            return false;

        }

        if (($filters['status'] ?? null) && $booking->status !== $filters['status']) {

            return false;

        }



        return true;

    }



    private static function checkoutChannelKey(EventBookingCheckout $checkout): string

    {

        if ($checkout->stripe_checkout_session_id) {

            return 'stripe';

        }

        if ($checkout->paypal_order_id) {

            return 'paypal';

        }

        if ($checkout->razorpay_order_id) {

            return 'razorpay';

        }

        if ($checkout->sslcommerz_tran_id) {

            return 'sslcommerz';

        }



        return 'other_online';

    }



    /**

     * Catalog value for bookings without an online gateway (cash, bank, comp, free).

     *

     * @return list<array{key: string, label: string, cents: int, count: int, color: string}>

     */

    private static function offlineCatalogBuckets(): array

    {

        if (! Schema::hasTable('event_bookings')) {

            return [];

        }



        $bookings = self::offlineBookingsQuery([])

            ->with(['ticket' => fn ($q) => $q->select('id', 'price', 'early_bird_price', 'early_bird_ends_at')])

            ->get(['id', 'event_ticket_id', 'offline_payment_method', 'created_at']);



        $buckets = [

            'cash' => ['key' => 'cash', 'label' => 'Cash (est.)', 'cents' => 0, 'count' => 0, 'color' => 'bg-amber-500'],

            'bank_transfer' => ['key' => 'bank_transfer', 'label' => 'Bank transfer (est.)', 'cents' => 0, 'count' => 0, 'color' => 'bg-teal-600'],

            'free' => ['key' => 'free', 'label' => 'Free registrations', 'cents' => 0, 'count' => 0, 'color' => 'bg-outline-variant'],

        ];



        foreach ($bookings as $booking) {

            $unitCents = (int) round(($booking->ticket?->effectiveUnitPrice() ?? 0) * 100);

            $method = (string) ($booking->offline_payment_method ?? '');



            if ($method === 'cash') {

                $buckets['cash']['cents'] += $unitCents;

                $buckets['cash']['count']++;

            } elseif ($method === 'bank_transfer') {

                $buckets['bank_transfer']['cents'] += $unitCents;

                $buckets['bank_transfer']['count']++;

            } elseif ($unitCents === 0) {

                $buckets['free']['count']++;

            }

        }



        return array_values($buckets);

    }

    /**
     * @param  Collection<int, EventBooking>|iterable<int, EventBooking>  $bookings
     */
    private static function sumOfflineBookingCents(iterable $bookings): int
    {
        $cents = 0;
        foreach ($bookings as $booking) {
            $method = (string) ($booking->offline_payment_method ?? '');
            if ($method !== 'cash' && $method !== 'bank_transfer') {
                continue;
            }
            $cents += (int) round(($booking->ticket?->effectiveUnitPrice() ?? 0) * 100);
        }

        return $cents;
    }

    /**

     * @param  Collection<int, EventBookingCheckout>  $paidCheckouts

     * @return list<array{event_id: int, title: string, cents: int, orders: int}>

     */

    private static function topEventsByPaidRevenue($paidCheckouts): array

    {

        $byEvent = [];

        foreach ($paidCheckouts as $checkout) {

            $eventId = (int) $checkout->event_id;

            if (! isset($byEvent[$eventId])) {

                $byEvent[$eventId] = ['cents' => 0, 'orders' => 0];

            }

            $byEvent[$eventId]['cents'] += (int) $checkout->amount_total_cents;

            $byEvent[$eventId]['orders']++;

        }



        if ($byEvent === []) {

            return [];

        }



        arsort($byEvent);

        $topIds = array_slice(array_keys($byEvent), 0, 5);

        $titles = Event::query()->whereIn('id', $topIds)->pluck('title', 'id');



        $out = [];

        foreach ($topIds as $eventId) {

            $row = $byEvent[$eventId];

            $out[] = [

                'event_id' => $eventId,

                'title' => (string) ($titles[$eventId] ?? 'Event #'.$eventId),

                'cents' => $row['cents'],

                'orders' => $row['orders'],

            ];

        }



        return $out;

    }



    /**

     * @return array{

     *     available: bool,

     *     currency: string,

     *     total_cents: int,

     *     this_month_cents: int,

     *     online_cents: int,

     *     offline_cents: int,

     *     paid_orders: int,

     *     breakdown: list<array{key: string, label: string, cents: int, count: int, color: string}>,

     *     chart_days: list<array{label: string, date: string, cents: int, height_percent: int}>,

     *     top_events: list<array{event_id: int, title: string, cents: int, orders: int}>

     * }

     */

    private static function emptySnapshot(): array

    {

        return [

            'available' => false,

            'currency' => 'usd',

            'total_cents' => 0,

            'this_month_cents' => 0,

            'online_cents' => 0,

            'offline_cents' => 0,

            'paid_orders' => 0,

            'breakdown' => [],

            'chart_days' => [],

            'top_events' => [],

        ];

    }

}

