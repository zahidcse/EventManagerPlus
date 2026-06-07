<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventBooking;
use App\Models\EventBookingCheckout;
use App\Models\SiteSetting;
use App\Support\AdditionalServiceInventory;
use App\Support\BookingOrderTotals;
use App\Support\BookingThankYouSession;
use App\Support\FulfillCheckoutResult;
use App\Support\PublicBookingPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Razorpay\Api\Errors\SignatureVerificationError;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Throwable;

class EventBookingCheckoutService
{
    public function __construct(
        private readonly EventBookingConfirmationNotifier $bookingConfirmationNotifier,
    ) {}

    public function startPayPalCheckout(
        Event $event,
        array $validated,
        array $payload,
        BookingOrderTotals $totals,
        SiteSetting $setting
    ): string {
        $paypal = new PayPalCheckoutApi($setting);
        $accessToken = $paypal->getAccessToken();
        if ($accessToken === null || $accessToken === '') {
            throw new \RuntimeException('PayPal authentication failed.');
        }

        $checkout = EventBookingCheckout::query()->create([
            'event_id' => $event->id,
            'status' => 'pending',
            'amount_total_cents' => $totals->payableTotalCents,
            'currency' => $totals->currency,
            'payload' => $payload,
        ]);

        $currencyUpper = strtoupper($totals->currency);
        $valueDecimal = number_format($totals->payableTotalCents / 100, 2, '.', '');

        try {
            $orderJson = $paypal->createOrder(
                $accessToken,
                route('events.booking.paypal.return', [], true),
                route('events.show', $event, true).'?payment=cancelled',
                $currencyUpper,
                $valueDecimal,
                (string) $checkout->id,
                $setting->site_name ?: (string) config('app.name', 'Events'),
            );
        } catch (Throwable $e) {
            $checkout->delete();
            throw $e;
        }

        $orderId = is_array($orderJson) && isset($orderJson['id']) && is_string($orderJson['id'])
            ? $orderJson['id']
            : '';
        $approveUrl = PayPalCheckoutApi::approveUrlFromOrderJson($orderJson);

        if ($orderId === '' || $approveUrl === '') {
            $checkout->delete();
            throw new \RuntimeException('PayPal did not return a checkout URL.');
        }

        $checkout->update(['paypal_order_id' => $orderId]);

        return $approveUrl;
    }

    /**
     * After the buyer returns from PayPal, capture (if needed) and create bookings.
     */
    public function completePayPalReturn(string $payPalOrderId, SiteSetting $setting): bool
    {
        $checkout = EventBookingCheckout::query()
            ->where('paypal_order_id', $payPalOrderId)
            ->first();

        if ($checkout === null) {
            return false;
        }

        if ($checkout->status === 'paid') {
            return true;
        }

        $paypal = new PayPalCheckoutApi($setting);
        $token = $paypal->getAccessToken();
        if ($token === null || $token === '') {
            return false;
        }

        $order = $paypal->getOrder($token, $payPalOrderId);
        if ($order === null) {
            return false;
        }

        $status = strtoupper((string) ($order['status'] ?? ''));
        $finalOrder = $order;

        if ($status === 'APPROVED') {
            $captured = $paypal->captureOrder($token, $payPalOrderId);
            if ($captured === null) {
                return false;
            }
            $finalOrder = $captured;
            $status = strtoupper((string) ($finalOrder['status'] ?? ''));
        }

        if ($status !== 'COMPLETED') {
            return false;
        }

        $units = $finalOrder['purchase_units'] ?? null;
        if (! is_array($units) || $units === []) {
            return false;
        }
        $pu = $units[0];
        $customId = is_array($pu) ? ($pu['custom_id'] ?? null) : null;
        if ((string) $customId !== (string) $checkout->id) {
            Log::warning('PayPal checkout custom_id mismatch', ['checkout_id' => $checkout->id]);

            return false;
        }

        $paid = PayPalCheckoutApi::capturedAmount($finalOrder);
        if ($paid === null) {
            return false;
        }
        [$cents, $cur] = $paid;
        if (strtoupper($checkout->currency) !== $cur) {
            Log::warning('PayPal currency mismatch', [
                'checkout_id' => $checkout->id,
                'expected' => $checkout->currency,
                'actual' => $cur,
            ]);

            return false;
        }
        if ($cents !== (int) $checkout->amount_total_cents) {
            Log::warning('PayPal amount mismatch', [
                'checkout_id' => $checkout->id,
                'expected_cents' => $checkout->amount_total_cents,
                'actual_cents' => $cents,
            ]);

            return false;
        }

        return $this->fulfillCheckout($checkout, null, $payPalOrderId, null, null);
    }

    public function startStripeCheckout(
        Event $event,
        array $validated,
        array $payload,
        BookingOrderTotals $totals,
        SiteSetting $setting
    ): string {
        Stripe::setApiKey($setting->stripe_secret_key);

        $checkout = EventBookingCheckout::query()->create([
            'event_id' => $event->id,
            'status' => 'pending',
            'amount_total_cents' => $totals->payableTotalCents,
            'currency' => $totals->currency,
            'payload' => $payload,
        ]);

        try {
            $session = Session::create([
                'mode' => 'payment',
                'success_url' => route('events.booking.stripe.return', [], true).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('events.show', $event, true).'?payment=cancelled',
                'line_items' => $totals->stripeLineItems,
                'customer_email' => $validated['email'],
                'metadata' => [
                    'checkout_id' => (string) $checkout->id,
                    'event_id' => (string) $event->id,
                ],
            ]);
        } catch (Throwable $e) {
            $checkout->delete();
            throw $e;
        }

        $checkout->update([
            'stripe_checkout_session_id' => $session->id,
        ]);

        return (string) ($session->url ?? '');
    }

    public function startRazorpayCheckout(
        Event $event,
        array $validated,
        array $payload,
        BookingOrderTotals $totals,
        SiteSetting $setting,
    ): string {
        $checkout = EventBookingCheckout::query()->create([
            'event_id' => $event->id,
            'status' => 'pending',
            'amount_total_cents' => $totals->payableTotalCents,
            'currency' => $totals->currency,
            'payload' => $payload,
        ]);

        $api = new \Razorpay\Api\Api((string) $setting->razorpay_key_id, (string) $setting->razorpay_key_secret);

        try {
            $rzOrder = $api->order->create([
                'receipt' => substr('ebco_'.$checkout->id, 0, 40),
                'amount' => $totals->payableTotalCents,
                'currency' => strtoupper($totals->currency),
                'notes' => [
                    'checkout_id' => (string) $checkout->id,
                    'event_id' => (string) $event->id,
                ],
            ]);
            $rzOrderId = isset($rzOrder['id']) ? (string) $rzOrder['id'] : '';
        } catch (Throwable $e) {
            $checkout->delete();
            throw $e;
        }

        if ($rzOrderId === '') {
            $checkout->delete();
            throw new \RuntimeException('Razorpay did not return an order id.');
        }

        $checkout->update(['razorpay_order_id' => $rzOrderId]);

        return URL::temporarySignedRoute(
            'events.booking.razorpay.pay',
            now()->addMinutes(45),
            ['checkout' => $checkout],
            absolute: true
        );
    }

    public function completeRazorpayCheckout(
        EventBookingCheckout $checkout,
        SiteSetting $setting,
        string $razorpayOrderId,
        string $paymentId,
        string $signature,
    ): bool {
        if ($checkout->razorpay_order_id === null
            || $checkout->razorpay_order_id === ''
            || $checkout->razorpay_order_id !== $razorpayOrderId) {
            Log::warning('Razorpay checkout order id mismatch', ['checkout_id' => $checkout->id]);

            return false;
        }

        if ($checkout->status === 'paid') {
            return true;
        }

        $api = new \Razorpay\Api\Api((string) $setting->razorpay_key_id, (string) $setting->razorpay_key_secret);

        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_signature' => $signature,
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $paymentId,
            ]);
        } catch (SignatureVerificationError $e) {
            Log::warning('Razorpay signature verification failed', ['exception' => $e->getMessage()]);

            return false;
        }

        try {
            $paymentArr = $api->payment->fetch($paymentId)->toArray();
        } catch (Throwable $e) {
            Log::warning('Razorpay payment fetch failed', ['exception' => $e->getMessage()]);

            return false;
        }

        if (($paymentArr['order_id'] ?? null) !== $checkout->razorpay_order_id) {
            Log::warning('Razorpay payment order_id mismatch');

            return false;
        }

        $amountPaid = isset($paymentArr['amount']) ? (int) $paymentArr['amount'] : 0;
        $curPaid = strtolower((string) ($paymentArr['currency'] ?? ''));
        if ($amountPaid !== (int) $checkout->amount_total_cents || $curPaid !== strtolower($checkout->currency)) {
            Log::warning('Razorpay amount/currency mismatch', [
                'checkout_id' => $checkout->id,
                'expected_amount' => $checkout->amount_total_cents,
                'paid_amount' => $amountPaid,
            ]);

            return false;
        }

        $payStatus = strtolower((string) ($paymentArr['status'] ?? ''));
        if (! in_array($payStatus, ['captured', 'authorized'], true)) {
            return false;
        }

        return $this->fulfillCheckout($checkout, null, null, $paymentId, null);
    }

    public function fulfillIfPaidSession(Session $session): bool
    {
        $checkoutId = $session->metadata->checkout_id ?? null;
        if ($checkoutId === null || $checkoutId === '') {
            return false;
        }

        $checkout = EventBookingCheckout::query()->whereKey((int) $checkoutId)->first();
        if ($checkout === null) {
            return false;
        }

        if ($checkout->stripe_checkout_session_id !== null && $checkout->stripe_checkout_session_id !== $session->id) {
            Log::warning('Stripe checkout session id mismatch', ['checkout_id' => $checkout->id]);

            return false;
        }

        if ($session->payment_status !== 'paid') {
            return false;
        }

        if ((int) $session->amount_total !== $checkout->amount_total_cents) {
            Log::warning('Stripe amount differs from checkout record', [
                'checkout_id' => $checkout->id,
                'expected_cents' => $checkout->amount_total_cents,
                'session_total_cents' => $session->amount_total,
            ]);
        }

        return $this->fulfillCheckout($checkout, (string) $session->id, null, null, null);
    }

    /**
     * Create SSLCommerz hosted session and return the GatewayPage URL.
     *
     * @throws Throwable
     */
    public function startSslCommerzCheckout(
        Event $event,
        array $validated,
        array $payload,
        BookingOrderTotals $totals,
        SiteSetting $setting,
    ): string {
        $checkout = EventBookingCheckout::query()->create([
            'event_id' => $event->id,
            'status' => 'pending',
            'amount_total_cents' => $totals->payableTotalCents,
            'currency' => $totals->currency,
            'payload' => $payload,
        ]);

        $tranId = $this->makeUniqueSslCommerzTranId((int) $checkout->id);

        try {
            $checkout->update(['sslcommerz_tran_id' => $tranId]);
        } catch (Throwable $e) {
            $checkout->delete();
            throw $e;
        }

        $currencyUpper = strtoupper((string) $totals->currency);
        $totalAmount = number_format($totals->payableTotalCents / 100, 2, '.', '');
        $cusName = mb_substr((string) $validated['attendee_name'], 0, 50);
        $cusEmail = mb_substr((string) $validated['email'], 0, 50);
        $phoneDigits = preg_replace('/\D+/', '', (string) ($validated['phone'] ?? ''));
        $cusPhone = mb_substr($phoneDigits !== '' ? $phoneDigits : '01900000000', 0, 20);

        $gw = new SslCommerzGateway($setting);

        /** @var array<string, string> $post */
        $post = [
            'store_id' => (string) $setting->sslcommerz_store_id,
            'store_passwd' => (string) $setting->sslcommerz_store_password,
            'total_amount' => $totalAmount,
            'currency' => $currencyUpper,
            'tran_id' => $tranId,
            'success_url' => route('events.booking.sslcommerz.success', [], true),
            'fail_url' => route('events.booking.sslcommerz.fail', [], true),
            'cancel_url' => route('events.booking.sslcommerz.cancel', [], true),
            'ipn_url' => route('events.booking.sslcommerz.ipn', [], true),
            'shipping_method' => 'NO',
            'product_name' => mb_substr('Tickets: '.$event->title, 0, 255),
            'product_category' => 'event',
            'product_profile' => 'non-physical-goods',
            'cus_name' => $cusName,
            'cus_email' => $cusEmail !== '' ? $cusEmail : 'customer@invalid.local',
            'cus_add1' => mb_substr($event->fullVenueAddressLine() ?: 'Address', 0, 50),
            'cus_add2' => '',
            'cus_city' => mb_substr($event->venue_city ?: 'Dhaka', 0, 50),
            'cus_state' => mb_substr((string) ($event->venue_state ?? 'Dhaka'), 0, 50),
            'cus_postcode' => '1000',
            'cus_country' => mb_substr($event->venue_country ?: 'Bangladesh', 0, 50),
            'cus_phone' => $cusPhone,
            'value_a' => (string) $checkout->id,
        ];

        try {
            $gatewayUrl = $gw->createHostedGatewayUrl($post);
        } catch (Throwable $e) {
            $checkout->delete();
            throw $e;
        }

        if ($gatewayUrl === null || $gatewayUrl === '') {
            $checkout->delete();
            throw new \RuntimeException('SSLCommerz did not return a payment URL.');
        }

        return $gatewayUrl;
    }

    /**
     * Validate SSLCommerz transaction by val_id server-side and fulfill when possible.
     * Returns refreshed checkout row when mapped to local session; otherwise null.
     */
    public function completeSslCommerzReturn(string $valId, SiteSetting $setting): ?EventBookingCheckout
    {
        $gw = new SslCommerzGateway($setting);

        $v = $gw->validateByValId($valId);
        if ($v === null) {
            Log::warning('SSLCommerz validation API unreadable');

            return null;
        }

        $apiStatus = strtoupper((string) ($v['status'] ?? ''));
        if (! in_array($apiStatus, ['VALID', 'VALIDATED'], true)) {
            Log::warning('SSLCommerz validation not confirmed', ['status' => $apiStatus]);

            return null;
        }

        $tranId = (string) ($v['tran_id'] ?? '');
        if ($tranId === '') {
            return null;
        }

        $checkout = EventBookingCheckout::query()
            ->where('sslcommerz_tran_id', $tranId)
            ->first();

        if ($checkout === null) {
            Log::warning('SSLCommerz tran_id unknown locally');

            return null;
        }

        $valueACheckout = isset($v['value_a']) ? trim((string) $v['value_a']) : '';
        if ($valueACheckout !== '' && $valueACheckout !== (string) $checkout->id) {
            Log::warning('SSLCommerz value_a/checkout mismatch');

            return null;
        }

        if ($checkout->status === 'paid') {
            return $checkout;
        }

        $currencyExpected = strtolower((string) $checkout->currency);
        $currencyPaid = strtolower((string) ($v['currency_type'] ?? $v['currency'] ?? ''));
        if ($currencyPaid !== $currencyExpected) {
            Log::warning('SSLCommerz currency mismatch', [
                'checkout_id' => $checkout->id,
                'expected' => $currencyExpected,
                'actual' => $currencyPaid,
            ]);

            return null;
        }

        $amountMinor = SslCommerzGateway::decimalToMinor((string) ($v['currency_amount'] ?? $v['amount'] ?? '0'));
        if ($amountMinor !== (int) $checkout->amount_total_cents) {
            Log::warning('SSLCommerz amount mismatch', [
                'checkout_id' => $checkout->id,
                'expected_minor' => $checkout->amount_total_cents,
                'actual_minor' => $amountMinor,
            ]);

            return null;
        }

        $risk = (string) ($v['risk_level'] ?? '0');
        if ($risk === '1') {
            Log::notice('SSLCommerz risk_level high', ['checkout_id' => $checkout->id]);
        }

        $this->fulfillCheckout($checkout, null, null, null, $valId);

        return $checkout->fresh();
    }

    /**
     * @throws \RuntimeException
     */
    private function makeUniqueSslCommerzTranId(int $checkoutId): string
    {
        for ($i = 0; $i < 16; ++$i) {
            $candidate = mb_substr('E'.$checkoutId.'_'.strtolower(bin2hex(random_bytes(6))), 0, 30);
            $exists = EventBookingCheckout::query()
                ->where('sslcommerz_tran_id', $candidate)
                ->exists();
            if (! $exists) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Could not allocate SSLCommerz tran_id.');
    }

    public function fulfillCheckout(
        EventBookingCheckout $checkout,
        ?string $stripeSessionId = null,
        ?string $paypalOrderId = null,
        ?string $razorpayPaymentId = null,
        ?string $sslcommerzValId = null,
    ): bool {
        $hasStripe = $stripeSessionId !== null && $stripeSessionId !== '';
        $hasPaypal = $paypalOrderId !== null && $paypalOrderId !== '';
        $hasRz = $razorpayPaymentId !== null && $razorpayPaymentId !== '';
        $hasSsl = $sslcommerzValId !== null && $sslcommerzValId !== '';

        if (! $hasStripe && ! $hasPaypal && ! $hasRz && ! $hasSsl) {
            return false;
        }

        $result = DB::transaction(function () use (
            $checkout,
            $stripeSessionId,
            $paypalOrderId,
            $razorpayPaymentId,
            $sslcommerzValId,
            $hasStripe,
            $hasPaypal,
            $hasRz,
            $hasSsl,
        ): FulfillCheckoutResult {
            /** @var EventBookingCheckout|null $locked */
            $locked = EventBookingCheckout::query()
                ->whereKey($checkout->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return new FulfillCheckoutResult(false, collect(), null);
            }

            if ($locked->status === 'paid') {
                return new FulfillCheckoutResult(true, collect(), null);
            }

            $event = Event::query()
                ->whereKey($locked->event_id)
                ->with([
                    'tickets' => fn ($q) => $q->orderBy('sort_order'),
                    'additionalServices' => fn ($q) => $q->orderBy('sort_order'),
                ])
                ->first();

            if ($event === null) {
                return new FulfillCheckoutResult(false, collect(), null);
            }

            $payload = $locked->payload;
            $error = PublicBookingPayload::validateInventory($event, $payload);
            if ($error !== null) {
                $locked->update(['status' => 'inventory_failed']);

                return new FulfillCheckoutResult(false, collect(), null);
            }

            $addonError = AdditionalServiceInventory::decrement($event, $payload);
            if ($addonError !== null) {
                $locked->update(['status' => 'inventory_failed']);

                return new FulfillCheckoutResult(false, collect(), null);
            }

            $created = EventBooking::createManyFromCartPayload($event, $payload, [
                'user_id' => $payload['user_id'] ?? null,
                'attendee_name' => $payload['attendee_name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'] ?? null,
                'status' => 'confirmed',
                'stripe_checkout_session_id' => $hasStripe ? $stripeSessionId : null,
                'paypal_order_id' => $hasPaypal ? $paypalOrderId : null,
                'razorpay_payment_id' => $hasRz ? $razorpayPaymentId : null,
                'sslcommerz_val_id' => $hasSsl ? $sslcommerzValId : null,
            ]);

            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
                'stripe_checkout_session_id' => $hasStripe ? $stripeSessionId : $locked->stripe_checkout_session_id,
                'paypal_order_id' => $hasPaypal ? $paypalOrderId : $locked->paypal_order_id,
            ]);

            return new FulfillCheckoutResult(true, $created, $event);
        });

        if ($result->fulfilled && $result->bookings->isNotEmpty() && $result->event !== null) {
            $this->bookingConfirmationNotifier->notify($result->event, $result->bookings);
        }

        return $result->fulfilled;
    }

    public function redirectAfterPaidCheckout(Request $request, Event $event, EventBookingCheckout $checkout): RedirectResponse
    {
        $checkout->refresh();

        $bookings = BookingThankYouSession::bookingsForCheckout($checkout);

        $payload = is_array($checkout->payload) ? $checkout->payload : [];
        $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : 0;

        $message = 'Thank you — your tickets are confirmed.';

        if ($userId <= 0) {
            return BookingThankYouSession::redirect($request, $event, $bookings, $message);
        }

        $authenticatedId = Auth::check() ? (int) Auth::id() : 0;

        if ($authenticatedId !== $userId) {
            Auth::loginUsingId($userId);
            $request->session()->regenerate();
        }

        return BookingThankYouSession::redirect(
            $request,
            $event,
            $bookings,
            $message.' You are signed in—open My account to see this order.',
            true,
        );
    }
}
