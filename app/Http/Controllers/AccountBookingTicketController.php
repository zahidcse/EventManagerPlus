<?php

namespace App\Http\Controllers;

use App\Models\EventBooking;
use App\Services\EventBookingTicketPdfGenerator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountBookingTicketController extends Controller
{
    public function __construct(
        private readonly EventBookingTicketPdfGenerator $bookingTicketPdf,
    ) {}

    public function show(Request $request, EventBooking $booking): Response
    {
        if ((int) $booking->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        try {
            $pdf = $this->bookingTicketPdf->bookingTicketPdf($booking);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        return $pdf->download('event-ticket-'.$booking->id.'.pdf');
    }
}
