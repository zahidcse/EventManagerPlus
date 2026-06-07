<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventBookingCheckout;
use App\Models\SiteSetting;
use App\Services\EventBookingCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class EventBookingSslCommerzController extends Controller
{
    public function success(Request $request, EventBookingCheckoutService $checkoutService): RedirectResponse
    {
        $valId = $this->valIdFrom($request);
        if ($valId === '') {
            return redirect()->route('home')->with(
                'book_error',
                'Missing payment reference. Contact the organizer if you were charged.'
            );
        }

        $setting = SiteSetting::instance();
        if (
            ! $setting->sslcommerz_enabled
            || ! filled($setting->sslcommerz_store_id)
            || ! filled($setting->sslcommerz_store_password)
        ) {
            return redirect()->route('home')->with('book_error', 'SSLCommerz is not configured.');
        }

        try {
            $checkout = $checkoutService->completeSslCommerzReturn($valId, $setting);
        } catch (\Throwable $e) {
            Log::error('SSLCommerz success handling failed', ['exception' => $e->getMessage()]);

            return $this->softFailRedirect(
                $request,
                'Could not verify SSLCommerz payment. If money was deducted, contact the organizer with your email.'
            );
        }

        if ($checkout === null) {
            return $this->softFailRedirect(
                $request,
                'Payment could not be verified. Contact the organizer if you were charged.'
            );
        }

        $event = Event::query()->whereKey($checkout->event_id)->first();
        if ($event === null) {
            return redirect()->route('home')->with('book_error', 'Event no longer exists.');
        }

        if ($checkout->status === 'paid') {
            return $checkoutService->redirectAfterPaidCheckout($request, $event, $checkout);
        }

        return redirect()->route('events.show', $event)->with(
            'book_error',
            $checkout->status === 'inventory_failed'
                ? 'Payment received, but inventory ran out. Please contact the organizer for a refund.'
                : 'Payment could not be finalized. Contact the organizer if you were charged.'
        );
    }

    public function ipn(Request $request, EventBookingCheckoutService $checkoutService): Response
    {
        $valId = $this->valIdFrom($request);
        if ($valId === '') {
            return response('Missing val_id', 400);
        }

        $setting = SiteSetting::instance();
        if (
            ! $setting->sslcommerz_enabled
            || ! filled($setting->sslcommerz_store_id)
            || ! filled($setting->sslcommerz_store_password)
        ) {
            return response('Not configured', 503);
        }

        try {
            $checkoutService->completeSslCommerzReturn($valId, $setting);
        } catch (\Throwable $e) {
            Log::error('SSLCommerz IPN processing failed', ['exception' => $e->getMessage()]);
        }

        return response('SUCCESS', 200, ['Content-Type' => 'text/plain']);
    }

    public function fail(Request $request): RedirectResponse
    {
        return $this->terminalRedirect($request, 'Payment was not completed. You can try booking again.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        return $this->terminalRedirect($request, 'Payment was cancelled.');
    }

    private function softFailRedirect(Request $request, string $message): RedirectResponse
    {
        [$event] = $this->resolveCheckoutContext($request);

        if ($event !== null) {
            return redirect()->route('events.show', $event)->with('book_error', $message);
        }

        return redirect()->route('home')->with('book_error', $message);
    }

    private function terminalRedirect(Request $request, string $message): RedirectResponse
    {
        [$event] = $this->resolveCheckoutContext($request);

        if ($event !== null) {
            return redirect()->route('events.show', $event)->with('book_error', $message);
        }

        return redirect()->route('home')->with('book_error', $message);
    }

    /**
     * @return array{0: Event|null, 1: EventBookingCheckout|null}
     */
    private function resolveCheckoutContext(Request $request): array
    {
        $tranId = trim((string) $request->input('tran_id', ''));
        if ($tranId === '') {
            return [null, null];
        }

        $checkout = EventBookingCheckout::query()
            ->where('sslcommerz_tran_id', $tranId)
            ->first();

        if ($checkout === null) {
            return [null, null];
        }

        $event = Event::query()->whereKey($checkout->event_id)->first();

        return [$event, $checkout];
    }

    private function valIdFrom(Request $request): string
    {
        $raw = $request->input('val_id');
        if (! is_string($raw)) {
            return '';
        }

        return mb_substr(trim($raw), 0, 50);
    }
}
