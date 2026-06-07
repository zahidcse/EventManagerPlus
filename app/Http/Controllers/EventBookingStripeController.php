<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventBookingCheckout;
use App\Models\SiteSetting;
use App\Services\EventBookingCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
use Throwable;

class EventBookingStripeController extends Controller
{
    public function return(Request $request, EventBookingCheckoutService $checkoutService)
    {
        $sessionId = $request->query('session_id');
        if (! is_string($sessionId) || $sessionId === '') {
            return redirect()->route('home')->with('book_error', 'Missing payment session.');
        }

        $checkout = EventBookingCheckout::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if ($checkout === null) {
            return redirect()->route('home')->with('book_error', 'Booking session not found.');
        }

        $event = Event::query()->whereKey($checkout->event_id)->first();
        if ($event === null) {
            return redirect()->route('home')->with('book_error', 'Event not found.');
        }

        $setting = SiteSetting::instance();
        if (! $setting->stripe_secret_key) {
            return redirect()->route('events.show', $event)->with('book_error', 'Payment is not configured.');
        }

        Stripe::setApiKey($setting->stripe_secret_key);

        try {
            $session = Session::retrieve($sessionId);
        } catch (Throwable $e) {
            Log::warning('Stripe session retrieve failed', ['e' => $e->getMessage()]);

            return redirect()->route('events.show', $event)->with(
                'book_error',
                'Could not verify payment. If you were charged, the organizer will confirm your booking shortly.'
            );
        }

        if (! $checkoutService->fulfillIfPaidSession($session)) {
            $checkout->refresh();
            if ($checkout->status === 'inventory_failed') {
                return redirect()->route('events.show', $event)->with(
                    'book_error',
                    'Payment received, but inventory ran out. Please contact the organizer for a refund.'
                );
            }

            if ($session->payment_status !== 'paid') {
                return redirect()->route('events.show', $event)->with('book_error', 'Payment was not completed.');
            }

            return redirect()->route('events.show', $event)->with(
                'book_error',
                'Could not finalize your booking. Please contact support with the email you used at checkout.'
            );
        }

        return $checkoutService->redirectAfterPaidCheckout($request, $event, $checkout);
    }

    public function webhook(Request $request): Response
    {
        $setting = SiteSetting::instance();
        $webhookSecret = $setting->stripe_webhook_secret;
        if (! $setting->stripe_enabled || $webhookSecret === null || $webhookSecret === '') {
            return response('Webhook not configured.', 400);
        }

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        if (! is_string($sigHeader) || $sigHeader === '') {
            return response('Missing signature.', 400);
        }

        try {
            $eventObj = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (Throwable $e) {
            Log::warning('Stripe webhook signature failed', ['e' => $e->getMessage()]);

            return response('Invalid signature.', 400);
        }

        if ($eventObj->type === 'checkout.session.completed') {
            Stripe::setApiKey($setting->stripe_secret_key);
            $raw = $eventObj->data->object;
            $sessionId = is_object($raw) && isset($raw->id) ? (string) $raw->id : null;
            if (is_string($sessionId) && $sessionId !== '') {
                try {
                    $session = Session::retrieve($sessionId);
                    app(EventBookingCheckoutService::class)->fulfillIfPaidSession($session);
                } catch (Throwable $e) {
                    Log::error('Stripe webhook fulfillment failed', ['e' => $e->getMessage()]);
                }
            }
        }

        return response('OK', 200);
    }
}
