<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EventBooking;
use App\Support\TicketBranding;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use InvalidArgumentException;

final class EventBookingTicketPdfGenerator
{
    public function bookingTicketPdf(EventBooking $booking): DomPdfWrapper
    {
        $booking->loadMissing(['event.organizer', 'ticket']);

        $event = $booking->event;
        if ($event === null) {
            throw new InvalidArgumentException('Booking has no event.');
        }
        $pdfFields = $event->ticketPdfFieldsResolved();

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

        $pdf = PdfFacade::loadView('pdf.event-booking-ticket', [
            'booking' => $booking,
            'event' => $event,
            'qrDataUri' => $qrResult->getDataUri(),
            'checkInUrl' => $checkInUrl,
            'unitPrice' => $unitPrice,
            'paymentRef' => $paymentRef,
            'pdfFields' => $pdfFields,
            'branding' => TicketBranding::forEvent($event),
            'forPdf' => true,
        ]);

        return $pdf->setPaper('a4', 'portrait');
    }
}
