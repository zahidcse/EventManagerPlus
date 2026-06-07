<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventBooking;
use App\Models\SiteSetting;
use App\Services\EventBookingTicketPdfGenerator;
use App\Support\TicketBranding;
use App\Support\BookingThankYouSession;
use App\Support\PublicFrontendTheme;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicBookingThankYouController extends Controller
{
    public function __construct(
        private readonly EventBookingTicketPdfGenerator $bookingTicketPdf,
    ) {}

    /**
     * @return array<string, mixed>
     */
    private function siteContext(): array
    {
        if (! Schema::hasTable('site_settings')) {
            $setting = new SiteSetting([
                'site_name' => config('app.name', 'Event Manager'),
                'frontend_theme' => 'default',
            ]);
        } else {
            $setting = SiteSetting::instance();
        }

        $extras = PublicFrontendTheme::publicPageExtras();

        return [
            'siteSetting' => $setting,
            'siteName' => $setting->site_name ?: config('app.name', 'Event Manager'),
            'siteLogoUrl' => PublicFrontendTheme::resolvePublicLogoUrl($setting),
            'contactEmail' => $extras['contactEmail'],
            'contactPhone' => $extras['contactPhone'],
            'heroImageUrl' => $extras['heroImageUrl'],
            'publicLayout' => PublicFrontendTheme::isClassicFamily()
                ? 'public.classic.layouts.app'
                : 'public.layouts.frontend-default',
        ];
    }

    public function show(Request $request, Event $event): View
    {
        $orderGroupIds = BookingThankYouSession::orderGroupIdsForEvent($request, $event);

        if ($orderGroupIds === [] && $request->user() !== null) {
            $orderGroupIds = EventBooking::query()
                ->where('user_id', $request->user()->id)
                ->where('event_id', $event->id)
                ->orderByDesc('created_at')
                ->pluck('order_group_id')
                ->filter(static fn ($id): bool => is_string($id) && $id !== '')
                ->unique()
                ->take(1)
                ->values()
                ->all();
        }

        if ($orderGroupIds === []) {
            abort(404);
        }

        $user = $request->user();
        $ticketsQuery = EventBooking::query()
            ->where('event_id', $event->id)
            ->whereIn('order_group_id', $orderGroupIds)
            ->with(['event', 'ticket'])
            ->orderBy('order_group_id')
            ->orderBy('id');

        if ($user !== null) {
            $ticketsQuery->where(function ($q) use ($user, $request, $event): void {
                $q->where('user_id', $user->id);
                $sessionIds = BookingThankYouSession::orderGroupIdsForEvent($request, $event);
                if ($sessionIds !== []) {
                    $q->orWhereIn('order_group_id', $sessionIds);
                }
            });
        } else {
            $data = $request->session()->get('booking_thank_you');
            $bookingIds = is_array($data['booking_ids'] ?? null) ? $data['booking_ids'] : [];
            $ticketsQuery->whereIn('id', array_map('intval', $bookingIds));
        }

        $tickets = $ticketsQuery->get();

        if ($tickets->isEmpty()) {
            abort(404);
        }

        $orders = $tickets->groupBy('order_group_id')->map(function ($groupTickets, string $groupId) {
            $primary = $groupTickets->sortBy('id')->first();

            return (object) [
                'order_group_id' => $groupId,
                'tickets' => $groupTickets,
                'primary' => $primary,
            ];
        })->values();

        $message = (string) $request->session()->get('booked', '');
        $accountReady = (bool) $request->session()->get('booked_account_ready', false);

        $view = PublicFrontendTheme::isClassicFamily()
            ? 'public.classic.booking.thank-you'
            : 'public.booking.thank-you';

        return view($view, array_merge($this->siteContext(), [
            'event' => $event,
            'orders' => $orders,
            'tickets' => $tickets,
            'message' => $message,
            'accountReady' => $accountReady,
        ]));
    }

    public function ticketPdf(Request $request, Event $event, EventBooking $booking): Response
    {
        $this->authorizeBookingAccess($request, $event, $booking);

        try {
            $pdf = $this->bookingTicketPdf->bookingTicketPdf($booking);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        return $pdf->download('event-ticket-'.$booking->id.'.pdf');
    }

    public function ticketPrint(Request $request, Event $event, EventBooking $booking): View
    {
        $this->authorizeBookingAccess($request, $event, $booking);

        $booking->loadMissing(['event.organizer', 'ticket']);
        $eventModel = $booking->event;
        if ($eventModel === null) {
            abort(404);
        }

        $checkInUrl = route('admin.check-in.show', ['token' => $booking->check_in_token], absolute: true);

        $qrResult = (new Builder(
            writer: new PngWriter(),
            validateResult: false,
            data: $checkInUrl,
            size: 200,
            margin: 8,
        ))->build();

        $ticket = $booking->ticket;
        $unitPrice = $ticket !== null ? $ticket->effectiveUnitPrice() : null;

        $paymentRef = null;
        if ($booking->offline_payment_reference !== null && $booking->offline_payment_reference !== '') {
            $offlineLabel = match ($booking->offline_payment_method) {
                'cash' => 'Cash',
                'bank_transfer' => 'Bank transfer',
                default => 'Payment',
            };
            $paymentRef = $offlineLabel.': '.$booking->offline_payment_reference;
        } elseif ($booking->stripe_checkout_session_id) {
            $paymentRef = 'Stripe: …'.substr((string) $booking->stripe_checkout_session_id, -10);
        } elseif ($booking->paypal_order_id) {
            $paymentRef = 'PayPal: …'.substr((string) $booking->paypal_order_id, -10);
        } elseif ($booking->razorpay_payment_id) {
            $paymentRef = 'Razorpay: …'.substr((string) $booking->razorpay_payment_id, -10);
        } elseif ($booking->sslcommerz_val_id) {
            $paymentRef = 'SSLCommerz: …'.substr((string) $booking->sslcommerz_val_id, -10);
        }

        return view('public.booking.ticket-print', [
            'booking' => $booking,
            'event' => $eventModel,
            'qrDataUri' => $qrResult->getDataUri(),
            'checkInUrl' => $checkInUrl,
            'unitPrice' => $unitPrice,
            'paymentRef' => $paymentRef,
            'pdfFields' => $eventModel->ticketPdfFieldsResolved(),
            'branding' => TicketBranding::forEvent($eventModel),
            'forPdf' => false,
            'autoPrint' => $request->boolean('print'),
        ]);
    }

    private function authorizeBookingAccess(Request $request, Event $event, EventBooking $booking): void
    {
        if ((int) $booking->event_id !== (int) $event->id) {
            abort(404);
        }

        if (BookingThankYouSession::canAccessBooking($request, $booking)) {
            return;
        }

        abort(403);
    }
}
