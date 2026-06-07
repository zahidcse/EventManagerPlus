<?php

namespace App\Support;

use App\Models\Event;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

/**
 * Shared online payment readiness for public booking totals (signup + checkout).
 */
final class BookingOnlineRedirect
{
    /**
     * @return array{stripe: bool, paypal: bool, razorpay: bool, sslcommerz: bool}
     */
    public static function gatewaysReady(BookingOrderTotals $totals, ?SiteSetting $setting): array
    {
        if ($setting === null) {
            return ['stripe' => false, 'paypal' => false, 'razorpay' => false, 'sslcommerz' => false];
        }

        $lines = $totals->stripeLineItems !== [];

        return [
            'stripe' => Edition::allowsPaymentGateway('stripe')
                && $setting->stripe_enabled
                && filled($setting->stripe_secret_key)
                && $lines,
            'paypal' => Edition::allowsPaymentGateway('paypal')
                && $setting->paypal_enabled
                && filled($setting->paypal_client_id)
                && filled($setting->paypal_secret)
                && $lines,
            'razorpay' => Edition::allowsPaymentGateway('razorpay')
                && $setting->razorpay_enabled
                && filled($setting->razorpay_key_id)
                && filled($setting->razorpay_key_secret)
                && $lines,
            'sslcommerz' => Edition::allowsPaymentGateway('sslcommerz')
                && $setting->sslcommerz_enabled
                && filled($setting->sslcommerz_store_id)
                && filled($setting->sslcommerz_store_password)
                && $lines,
        ];
    }

    /**
     * Guest signup on the booking form is only honored when checkout completes without an off-site gateway.
     */
    public static function allowsGuestSignupOnBookingForm(Request $request, Event $event, ?SiteSetting $setting): bool
    {
        $event->loadMissing([
            'tickets' => fn ($q) => $q->orderBy('sort_order'),
            'additionalServices' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $totals = BookingOrderTotals::fromEventAndRequest($event, $request);

        if ($totals->payableTotalCents <= 0) {
            return true;
        }

        $g = self::gatewaysReady($totals, $setting);

        return ! ($g['stripe'] || $g['paypal'] || $g['razorpay'] || $g['sslcommerz']);
    }
}
