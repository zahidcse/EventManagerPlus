<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminBookingNotificationDismissal;
use App\Models\EventBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminBookingNotificationController extends Controller
{
    public function dismiss(Request $request, EventBooking $booking): RedirectResponse|JsonResponse
    {
        $admin = $request->user();
        if ($admin === null || ! $admin->is_admin) {
            abort(403);
        }

        $tableMissing = false;
        try {
            if (! Schema::hasTable('admin_booking_notification_dismissals')) {
                $tableMissing = true;
            }
        } catch (\Throwable) {
            $tableMissing = true;
        }

        if (! $tableMissing) {
            AdminBookingNotificationDismissal::query()->firstOrCreate([
                'user_id' => $admin->id,
                'event_booking_id' => $booking->id,
            ]);
        }

        $event = $booking->event;
        $redirectUrl = $event !== null
            ? route('admin.events.bookings', $event)
            : route('admin.dashboard');

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->to($redirectUrl);
    }
}
