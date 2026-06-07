<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventBookingCheckout;
use App\Models\SiteSetting;
use App\Services\EventBookingCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventBookingPayPalController extends Controller
{
    public function return(Request $request, EventBookingCheckoutService $checkoutService): RedirectResponse
    {
        $token = $request->query('token');
        if (! is_string($token) || $token === '') {
            return redirect()->route('home')->with('book_error', 'Missing PayPal order.');
        }

        $checkout = EventBookingCheckout::query()
            ->where('paypal_order_id', $token)
            ->first();

        if ($checkout === null) {
            return redirect()->route('home')->with('book_error', 'Booking session not found.');
        }

        $event = Event::query()->whereKey($checkout->event_id)->first();
        if ($event === null) {
            return redirect()->route('home')->with('book_error', 'Event not found.');
        }

        $setting = SiteSetting::instance();
        if (! $setting->paypal_enabled || ! filled($setting->paypal_client_id) || ! filled($setting->paypal_secret)) {
            return redirect()->route('events.show', $event)->with('book_error', 'PayPal is not configured.');
        }

        try {
            $ok = $checkoutService->completePayPalReturn($token, $setting);
        } catch (\Throwable $e) {
            Log::error('PayPal return handling failed', ['e' => $e->getMessage()]);

            return redirect()->route('events.show', $event)->with(
                'book_error',
                'Could not confirm PayPal payment. If you were charged, contact the organizer with your payment email.'
            );
        }

        if (! $ok) {
            $checkout->refresh();
            if ($checkout->status === 'inventory_failed') {
                return redirect()->route('events.show', $event)->with(
                    'book_error',
                    'Payment received, but inventory ran out. Please contact the organizer for a refund.'
                );
            }

            return redirect()->route('events.show', $event)->with(
                'book_error',
                'Payment was not completed. You can try booking again.'
            );
        }

        return $checkoutService->redirectAfterPaidCheckout($request, $event, $checkout);
    }
}
