<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventBookingCheckout;
use App\Models\SiteSetting;
use App\Services\EventBookingCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class EventBookingRazorpayController extends Controller
{
    public function pay(Request $request, EventBookingCheckout $checkout): View|RedirectResponse
    {
        $event = Event::query()->whereKey($checkout->event_id)->first();

        $setting = Schema::hasTable('site_settings') ? SiteSetting::instance() : null;
        $razorpayReady = $setting
            && $setting->razorpay_enabled
            && filled($setting->razorpay_key_id)
            && filled($setting->razorpay_key_secret);

        if ($event === null || !$razorpayReady) {
            abort(404);
        }

        if ($checkout->status === 'paid') {
            return app(EventBookingCheckoutService::class)->redirectAfterPaidCheckout($request, $event, $checkout);
        }

        if ($checkout->status !== 'pending' || $checkout->razorpay_order_id === null || $checkout->razorpay_order_id === '') {
            return redirect()
                ->route('events.show', $event)
                ->with('book_error', 'This checkout session is no longer valid. Please try booking again.');
        }

        $payload = is_array($checkout->payload) ? $checkout->payload : [];

        return view('public.booking.razorpay-pay', [
            'event' => $event,
            'checkout' => $checkout,
            'razorpayKeyId' => (string) $setting->razorpay_key_id,
            'razorpayOrderId' => (string) $checkout->razorpay_order_id,
            'amountCents' => (int) $checkout->amount_total_cents,
            'currency' => strtoupper($checkout->currency),
            'contactName' => (string) ($payload['attendee_name'] ?? ''),
            'contactEmail' => (string) ($payload['email'] ?? ''),
            'contactPhone' => (string) ($payload['phone'] ?? ''),
            'verifyUrl' => route('events.booking.razorpay.verify'),
            'cancelUrl' => route('events.show', $event) . '?payment=cancelled',
            'siteName' => $setting->site_name ?: (string) config('app.name', 'Events'),
        ]);
    }

    public function verify(Request $request, EventBookingCheckoutService $checkoutService): RedirectResponse
    {
        $data = $request->validate([
            'checkout_id' => ['required', 'integer', 'exists:event_booking_checkouts,id'],
            'razorpay_order_id' => ['required', 'string', 'max:48'],
            'razorpay_payment_id' => ['required', 'string', 'max:48'],
            'razorpay_signature' => ['required', 'string', 'max:500'],
        ]);

        $checkout = EventBookingCheckout::query()->find((int) $data['checkout_id']);
        if ($checkout === null) {
            return redirect()->route('home')->with('book_error', 'Checkout not found.');
        }

        $event = Event::query()->whereKey($checkout->event_id)->first();
        if ($event === null) {
            return redirect()->route('home')->with('book_error', 'Event not found.');
        }

        $setting = SiteSetting::instance();
        if (
            !$setting->razorpay_enabled
            || !filled($setting->razorpay_key_id)
            || !filled($setting->razorpay_key_secret)
        ) {
            return redirect()
                ->route('events.show', $event)
                ->with('book_error', 'Razorpay is not configured.');
        }

        try {
            $ok = $checkoutService->completeRazorpayCheckout(
                $checkout,
                $setting,
                (string) $data['razorpay_order_id'],
                (string) $data['razorpay_payment_id'],
                (string) $data['razorpay_signature'],
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('events.show', $event)
                ->with(
                    'book_error',
                    'Could not verify Razorpay payment. If money was deducted, please contact support with your email and payment receipt.'
                );
        }

        if (!$ok) {
            $checkout->refresh();

            return redirect()->route('events.show', $event)->with(
                'book_error',
                $checkout->status === 'inventory_failed'
                ? 'Payment received, but inventory ran out. Please contact the organizer for a refund.'
                : 'Could not finalize your Razorpay payment. Contact support if you were charged.'
            );
        }

        return $checkoutService->redirectAfterPaidCheckout($request, $event, $checkout->fresh());
    }
}
