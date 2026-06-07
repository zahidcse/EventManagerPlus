<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingCheckInController extends Controller
{
    public function show(string $token): View
    {
        $booking = EventBooking::query()
            ->where('check_in_token', $token)
            ->with(['event.organizer', 'ticket'])
            ->firstOrFail();

        return view('admin.bookings.check-in', [
            'activeNav' => 'events',
            'booking' => $booking,
            'event' => $booking->event,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $booking = EventBooking::query()
            ->where('check_in_token', $token)
            ->firstOrFail();

        if ($booking->checked_in_at !== null) {
            return redirect()
                ->route('admin.check-in.show', ['token' => $token])
                ->with('info', 'This attendee was already checked in on '.$booking->checked_in_at->format('M j, Y g:i A').'.');
        }

        $booking->markCheckedIn();

        return redirect()
            ->route('admin.check-in.show', ['token' => $token])
            ->with('success', 'Attendee checked in successfully.');
    }
}
