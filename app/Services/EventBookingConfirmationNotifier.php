<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\EventRegistrationConfirmedMail;
use App\Models\Event;
use App\Models\EventBooking;
use App\Models\SiteSetting;
use App\Support\EventRegistrationEmailContent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EventBookingConfirmationNotifier
{
    public function __construct(
        private readonly EventBookingTicketPdfGenerator $bookingTicketPdf,
    ) {}

    /**
     * @param  Collection<int, EventBooking>  $bookingsForOrder  All booking rows created in one checkout/order
     */
    public function notify(Event $event, Collection $bookingsForOrder): void
    {
        if ($bookingsForOrder->isEmpty()) {
            return;
        }

        $bookings = new EloquentCollection($bookingsForOrder->values()->all());
        $bookings->loadMissing('ticket');

        /** @var EventBooking|null $primary */
        $primary = $bookings->sortBy('id')->first();
        if ($primary === null) {
            return;
        }

        $attendeeEmail = trim((string) $primary->email);
        if ($attendeeEmail === '') {
            return;
        }

        $siteSetting = null;
        if (Schema::hasTable('site_settings')) {
            try {
                $siteSetting = SiteSetting::query()->first();
            } catch (Throwable) {
                $siteSetting = null;
            }
        }

        $siteName = $siteSetting?->site_name ?: (string) config('app.name', 'Events');

        $content = EventRegistrationEmailContent::build($event, $primary, $bookings, $siteName);
        $subject = $content['subject'];
        $bodyText = $content['body'];

        $pdfAttachments = $this->buildTicketPdfAttachments($bookings);

        try {
            Mail::to($attendeeEmail)->send(new EventRegistrationConfirmedMail($subject, $bodyText, $pdfAttachments));
        } catch (Throwable $e) {
            Log::warning('Booking confirmation email failed (attendee)', [
                'event_id' => $event->id,
                'to' => $attendeeEmail,
                'exception' => $e->getMessage(),
            ]);
        }

        $adminEmail = trim((string) ($siteSetting?->contact_email ?? ''));
        if ($adminEmail === '') {
            return;
        }

        if (mb_strtolower($adminEmail) === mb_strtolower($attendeeEmail)) {
            return;
        }

        try {
            Mail::to($adminEmail)->send(new EventRegistrationConfirmedMail($subject, $bodyText, $pdfAttachments));
        } catch (Throwable $e) {
            Log::warning('Booking confirmation email failed (site admin)', [
                'event_id' => $event->id,
                'to' => $adminEmail,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, EventBooking>  $bookings
     * @return list<Attachment>
     */
    private function buildTicketPdfAttachments(Collection $bookings): array
    {
        $attachments = [];

        foreach ($bookings->sortBy('id')->values() as $booking) {
            if (! $booking instanceof EventBooking) {
                continue;
            }
            try {
                $binary = $this->bookingTicketPdf->bookingTicketPdf($booking)->output();
                if ($binary === '') {
                    continue;
                }
                $filename = 'event-ticket-'.$booking->id.'.pdf';
                $attachments[] = Attachment::fromData(
                    function () use ($binary): string {
                        return $binary;
                    },
                    $filename,
                )->withMime('application/pdf');
            } catch (Throwable $e) {
                Log::warning('Booking confirmation PDF attachment skipped', [
                    'booking_id' => $booking->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $attachments;
    }
}
