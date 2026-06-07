<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use App\Models\Organizer;
use App\Support\AdminDashboardRevenue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        $bookings = $this
            ->bookingQuery($filters)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $revenue = AdminDashboardRevenue::revenueForFilters($filters);

        return view('admin.reports.index', [
            'activeNav' => 'reports',
            'activeTab' => 'registrations',
            'bookings' => $bookings,
            'filters' => $filters,
            'eventsForFilter' => Event::query()->orderBy('title')->get(['id', 'title', 'starts_at']),
            'organizersForFilter' => Organizer::query()->orderBy('name')->get(['id', 'name']),
            'revenue' => $revenue,
            'totalMatching' => $bookings->total(),
        ]);
    }

    public function orders(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        // We want to group by order_group_id.
        // To ensure filters still apply, we filter the underlying bookings first.
        $filteredOrderGroupIds = $this->bookingQuery($filters)
            ->select('order_group_id')
            ->distinct()
            ->pluck('order_group_id');

        $orders = EventBooking::query()
            ->whereIn('order_group_id', $filteredOrderGroupIds)
            ->with(['event.organizer', 'ticket'])
            ->select(
                'order_group_id',
                DB::raw('COUNT(*) as ticket_count'),
                DB::raw('MIN(created_at) as created_at'),
                DB::raw('MIN(event_id) as event_id'),
                DB::raw('MIN(attendee_name) as main_attendee_name'),
                DB::raw('MIN(email) as main_email'),
                DB::raw('MIN(status) as group_status'),
                DB::raw('GROUP_CONCAT(DISTINCT notes SEPARATOR "|||") as combined_notes')
            )
            ->groupBy('order_group_id')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // For each order, we want the unique ticket types.
        $orders->getCollection()->each(function ($order) {
            $order->ticket_types = EventBooking::query()
                ->where('order_group_id', $order->order_group_id)
                ->join('event_tickets', 'event_bookings.event_ticket_id', '=', 'event_tickets.id')
                ->distinct()
                ->pluck('event_tickets.name');

            // Parse additional services from combined_notes if possible
            // Our notes format is: "Session: ... \n Add-ons: Service X x1; Service Y x2"
            $allNotes = (string) $order->combined_notes;
            $services = [];
            if (preg_match_all('/Add-ons:\s*(.*)/', $allNotes, $matches)) {
                foreach ($matches[1] as $m) {
                    $parts = explode(';', $m);
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if ($p !== '') {
                            $services[] = $p;
                        }
                    }
                }
            }
            $order->parsed_services = array_unique($services);
        });

        return view('admin.reports.orders', [
            'activeNav' => 'reports',
            'activeTab' => 'orders',
            'orders' => $orders,
            'filters' => $filters,
            'eventsForFilter' => Event::query()->orderBy('title')->get(['id', 'title', 'starts_at']),
            'organizersForFilter' => Organizer::query()->orderBy('name')->get(['id', 'name']),
            'totalMatching' => $orders->total(),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);

        $filename = 'event-registrations-report-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($filters): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, [
                'Booked at',
                'Attendee name',
                'Email',
                'Phone',
                'Status',
                'Offline payment method',
                'Offline payment reference',
                'Checked in at',
                'Ticket',
                'Ticket price',
                'Event',
                'Event start',
                'Event end',
                'Location',
                'Organizer',
                'Speakers',
                'Category',
            ]);

            $this->bookingQuery($filters)
                ->orderByDesc('created_at')
                ->with(['event.organizer', 'event.speakers', 'event.eventCategory', 'ticket'])
                ->chunk(500, function ($rows) use ($out): void {
                    foreach ($rows as $b) {
                        /** @var EventBooking $b */
                        $ev = $b->event;
                        $speakers = $ev?->speakers->pluck('name')->filter()->implode('; ') ?? '';
                        fputcsv($out, [
                            $b->created_at?->format('Y-m-d H:i'),
                            $b->attendee_name,
                            $b->email,
                            $b->phone,
                            $b->status,
                            $b->offline_payment_method,
                            $b->offline_payment_reference,
                            $b->checked_in_at?->format('Y-m-d H:i'),
                            $b->ticket?->name,
                            $b->ticket?->price,
                            $ev?->title,
                            $ev?->starts_at?->format('Y-m-d H:i'),
                            $ev?->ends_at?->format('Y-m-d H:i'),
                            $ev?->fullVenueAddressLine() ?: $ev?->locationLabel(),
                            $ev?->organizer?->name,
                            $speakers,
                            $ev?->categoryLabel(),
                        ]);
                    }
                });

            fclose($out);
        }, 200, $headers);
    }

    /**
     * @return array{
     *     date_from: ?string,
     *     date_to: ?string,
     *     event_id: ?int,
     *     organizer_id: ?int,
     *     check_in: ?string,
     *     location: ?string,
     *     status: ?string,
     * }
     */
    private function validatedFilters(Request $request): array
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $from = is_string($dateFrom) && $dateFrom !== '' ? $dateFrom : null;
        $to = is_string($dateTo) && $dateTo !== '' ? $dateTo : null;

        $eventId = $request->query('event_id');
        $organizerId = $request->query('organizer_id');
        $location = $request->query('location');
        $checkIn = $request->query('check_in');
        $checkInFilter = is_string($checkIn) && in_array($checkIn, ['checked_in', 'not_checked_in'], true)
            ? $checkIn
            : null;

        $status = $request->query('status');

        return [
            'date_from' => $from,
            'date_to' => $to,
            'event_id' => is_numeric($eventId) ? (int) $eventId : null,
            'organizer_id' => is_numeric($organizerId) ? (int) $organizerId : null,
            'check_in' => $checkInFilter,
            'location' => is_string($location) && trim($location) !== '' ? trim($location) : null,
            'status' => is_string($status) && $status !== '' ? $status : null,
        ];
    }

    /**
     * @param  array{
     *     date_from: ?string,
     *     date_to: ?string,
     *     event_id: ?int,
     *     organizer_id: ?int,
     *     check_in: ?string,
     *     location: ?string,
     * }  $filters
     * @return Builder<EventBooking>
     */
    private function bookingQuery(array $filters): Builder
    {
        $query = EventBooking::query()->with(['event.organizer', 'event.speakers', 'event.eventCategory', 'ticket']);

        $this->applyCheckInFilterToBookings($query, $filters);

        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        $query->whereHas('event', function (Builder $eq) use ($filters): void {
            if ($filters['event_id']) {
                $eq->whereKey($filters['event_id']);
            }
            if ($filters['organizer_id']) {
                $eq->where('organizer_id', $filters['organizer_id']);
            }
            if ($filters['location']) {
                $term = '%' . addcslashes($filters['location'], '%_\\') . '%';
                $eq->where(function (Builder $lq) use ($term): void {
                    $lq->where('venue_city', 'like', $term)
                        ->orWhere('venue_state', 'like', $term)
                        ->orWhere('venue_country', 'like', $term)
                        ->orWhere('venue_street', 'like', $term);
                });
            }

            $from = $filters['date_from'];
            $to = $filters['date_to'];
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
        });

        return $query;
    }

    /** @param  Builder<EventBooking>  $query */
    private function applyCheckInFilterToBookings(Builder $query, array $filters): void
    {
        if ($filters['check_in'] === 'checked_in') {
            $query->whereNotNull('checked_in_at');

            return;
        }
        if ($filters['check_in'] === 'not_checked_in') {
            $query->whereNull('checked_in_at');
        }
    }

}
