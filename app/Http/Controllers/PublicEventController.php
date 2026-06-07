<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicBookingRequest;
use App\Models\Event;
use App\Models\EventBooking;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\EventBookingCheckoutService;
use App\Services\EventBookingConfirmationNotifier;
use App\Support\AdditionalServiceInventory;
use App\Support\BookingOnlineRedirect;
use App\Support\BookingOrderTotals;
use App\Support\BookingThankYouSession;
use App\Support\Edition;
use App\Support\PublicBookingPayload;
use App\Support\PublicFrontendTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PublicEventController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function siteContext(): array
    {
        if (! Schema::hasTable('site_settings')) {
            $setting = new SiteSetting([
                'site_name' => config('app.name', 'Event Manager'),
                'frontend_theme' => 'default',
            ]);
        } else {
            $setting = SiteSetting::instance();
        }

        $extras = PublicFrontendTheme::publicPageExtras();

        return [
            'siteSetting' => $setting,
            'siteName' => $setting->site_name ?: config('app.name', 'Event Manager'),
            'siteLogoUrl' => PublicFrontendTheme::resolvePublicLogoUrl($setting),
            'contactEmail' => $extras['contactEmail'],
            'contactPhone' => $extras['contactPhone'],
            'heroImageUrl' => $extras['heroImageUrl'],
        ];
    }

    public function index(): View
    {
        $eventsQuery = Event::query()
            ->publicActive()
            ->with(['eventCategory', 'tickets'])
            ->whereNotNull('starts_at')
            ->upcomingForPublicListing()
            ->orderBy('starts_at');

        $q = trim((string) request('q', ''));
        if ($q !== '') {
            $term = '%'.addcslashes($q, '%_\\').'%';
            $eventsQuery->where('title', 'like', $term);
        }

        $city = trim((string) request('city', ''));
        if ($city !== '') {
            $ct = '%'.addcslashes($city, '%_\\').'%';
            $eventsQuery->where(function ($sub) use ($ct) {
                $sub->where('venue_city', 'like', $ct)
                    ->orWhere('venue_state', 'like', $ct);
            });
        }

        $date = request('date');
        if (is_string($date) && $date !== '') {
            try {
                $eventsQuery->whereDate('starts_at', Carbon::parse($date)->toDateString());
            } catch (\Throwable) {
            }
        }

        $events = $eventsQuery->paginate(12)->withQueryString();

        return view(PublicFrontendTheme::eventView('index'), array_merge($this->siteContext(), [
            'events' => $events,
        ]));
    }

    public function show(Event $event): View
    {
        abort_unless($event->visibility === 'public' && $event->status === 'active', 404);

        $event->load([
            'organizer',
            'eventCategory',
            'tickets' => fn ($q) => $q->orderBy('sort_order'),
            'additionalServices' => fn ($q) => $q->orderBy('sort_order'),
            'faqs' => fn ($q) => $q->orderBy('sort_order'),
            'timelineItems' => fn ($q) => $q->orderBy('sort_order'),
            'galleryImages' => fn ($q) => $q->orderBy('sort_order'),
            'speakers',
        ]);

        $event->tickets->each(fn ($t) => $t->setRelation('event', $event));

        $galleryUrls = collect();
        if ($event->cover_image_path) {
            $galleryUrls->push(asset('uploads/'.$event->cover_image_path));
        }
        foreach ($event->galleryImages as $img) {
            $galleryUrls->push(asset('uploads/'.$img->path));
        }
        $galleryUrls = $galleryUrls->unique()->values();

        $bookableTickets = $event->tickets->filter(fn ($t) => $t->isBookableNow());
        $priceFrom = $bookableTickets->min(fn ($t) => $t->effectiveUnitPrice());
        if ($priceFrom === null && $event->tickets->isNotEmpty()) {
            $priceFrom = $event->tickets->min(fn ($t) => $t->effectiveUnitPrice());
        }

        $setting = Schema::hasTable('site_settings') ? SiteSetting::instance() : null;
        $showStripePayments = Edition::allowsPaymentGateway('stripe')
            && $setting
            && $setting->stripe_enabled
            && filled($setting->stripe_secret_key);
        $showPayPalPayments = Edition::allowsPaymentGateway('paypal')
            && $setting
            && $setting->paypal_enabled
            && filled($setting->paypal_client_id)
            && filled($setting->paypal_secret);
        $showRazorpayPayments = Edition::allowsPaymentGateway('razorpay')
            && $setting
            && $setting->razorpay_enabled
            && filled($setting->razorpay_key_id)
            && filled($setting->razorpay_key_secret);
        $showSslCommerzPayments = Edition::allowsPaymentGateway('sslcommerz')
            && $setting
            && $setting->sslcommerz_enabled
            && filled($setting->sslcommerz_store_id)
            && filled($setting->sslcommerz_store_password);
        $showCashOfflinePayments = Edition::allowsPaymentGateway('cash')
            && $setting
            && $setting->payment_cash_enabled;
        $showBankOfflinePayments = Edition::allowsPaymentGateway('bank_transfer')
            && $setting
            && $setting->payment_bank_transfer_enabled;

        $user = request()->user();

        return view(PublicFrontendTheme::eventView('show'), array_merge($this->siteContext(), [
            'event' => $event,
            'galleryUrls' => $galleryUrls,
            'priceFrom' => $priceFrom !== null ? (float) $priceFrom : null,
            'showStripePayments' => $showStripePayments,
            'showPayPalPayments' => $showPayPalPayments,
            'showRazorpayPayments' => $showRazorpayPayments,
            'showSslCommerzPayments' => $showSslCommerzPayments,
            'showCashOfflinePayments' => $showCashOfflinePayments,
            'showBankOfflinePayments' => $showBankOfflinePayments,
            'bankTransferInstructions' => $setting ? trim((string) ($setting->bank_transfer_instructions ?? '')) : '',
            'bookingDefaults' => [
                'attendee_name' => old('attendee_name', $user?->name ?? ''),
                'email' => old('email', $user?->email ?? ''),
                'phone' => old('phone', ''),
            ],
            'attendeeSettings' => $event->attendeeSettingsResolved(),
            'attendeeFieldDefinitions' => Event::attendeeFieldDefinitions(),
            'bookingAuthReturnPath' => '/events/'.$event->slug,
        ]));
    }

    public function book(
        StorePublicBookingRequest $request,
        Event $event,
        EventBookingCheckoutService $checkoutService,
        EventBookingConfirmationNotifier $bookingConfirmationNotifier,
    ): RedirectResponse
    {
        abort_unless($event->visibility === 'public' && $event->status === 'active', 404);

        $validated = $request->validated();
        $sanitizedForCheckout = Arr::except($validated, ['password', 'password_confirmation', 'create_account']);

        $event->load([
            'tickets' => fn ($q) => $q->orderBy('sort_order'),
            'additionalServices' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $registeredViaBookingSignup = false;

        if ($request->user() === null && $request->boolean('create_account') && isset($validated['password'])) {
            $customer = User::query()->create([
                'name' => $sanitizedForCheckout['attendee_name'],
                'email' => $sanitizedForCheckout['email'],
                'password' => $validated['password'],
            ]);

            Auth::login($customer);

            $request->session()->regenerate();

            $registeredViaBookingSignup = true;
        }

        $payload = PublicBookingPayload::fromRequest($event, $request, $sanitizedForCheckout);

        $inventoryError = PublicBookingPayload::validateInventory($event, $payload);
        if ($inventoryError !== null) {
            return back()->withInput()->with('book_error', $inventoryError);
        }

        $totals = BookingOrderTotals::fromEventAndRequest($event, $request);

        $setting = Schema::hasTable('site_settings') ? SiteSetting::instance() : null;
        ['stripe' => $stripeReady, 'paypal' => $paypalReady, 'razorpay' => $razorpayReady, 'sslcommerz' => $sslCommerzReady] = BookingOnlineRedirect::gatewaysReady($totals, $setting);

        $payable = $totals->payableTotalCents > 0;
        $paymentMethod = (string) $request->input('payment_method', '');
        $offlineReference = mb_substr(trim((string) $request->input('offline_payment_reference', '')), 0, 191);

        if ($payable && $paymentMethod === 'stripe' && $stripeReady) {
            try {
                $checkoutUrl = $checkoutService->startStripeCheckout($event, $sanitizedForCheckout, $payload, $totals, $setting);
            } catch (\Throwable $e) {
                Log::error('Stripe checkout failed', ['exception' => $e->getMessage()]);

                return back()->withInput()->with('book_error', 'Could not start payment. Try again or contact the organizer.');
            }

            if ($checkoutUrl === '') {
                return back()->withInput()->with('book_error', 'Could not start payment.');
            }

            return redirect()->away($checkoutUrl);
        }

        if ($payable && $paymentMethod === 'paypal' && $paypalReady) {
            try {
                $checkoutUrl = $checkoutService->startPayPalCheckout($event, $sanitizedForCheckout, $payload, $totals, $setting);
            } catch (\Throwable $e) {
                Log::error('PayPal checkout failed', ['exception' => $e->getMessage()]);

                return back()->withInput()->with('book_error', 'Could not start PayPal checkout. Try again or contact the organizer.');
            }

            if ($checkoutUrl === '') {
                return back()->withInput()->with('book_error', 'Could not start payment.');
            }

            return redirect()->away($checkoutUrl);
        }

        if ($payable && $paymentMethod === 'razorpay' && $razorpayReady) {
            try {
                $checkoutUrl = $checkoutService->startRazorpayCheckout($event, $sanitizedForCheckout, $payload, $totals, $setting);
            } catch (\Throwable $e) {
                Log::error('Razorpay checkout failed', ['exception' => $e->getMessage()]);

                return back()->withInput()->with('book_error', 'Could not start Razorpay payment. Try again or contact the organizer.');
            }

            if ($checkoutUrl === '') {
                return back()->withInput()->with('book_error', 'Could not start payment.');
            }

            return redirect()->away($checkoutUrl);
        }

        if ($payable && $paymentMethod === 'sslcommerz' && $sslCommerzReady) {
            try {
                $checkoutUrl = $checkoutService->startSslCommerzCheckout($event, $sanitizedForCheckout, $payload, $totals, $setting);
            } catch (\Throwable $e) {
                Log::error('SSLCommerz checkout failed', ['exception' => $e->getMessage()]);

                return back()->withInput()->with('book_error', 'Could not start SSLCommerz payment. Try again or contact the organizer.');
            }

            if ($checkoutUrl === '') {
                return back()->withInput()->with('book_error', 'Could not start payment.');
            }

            return redirect()->away($checkoutUrl);
        }

        $useOfflinePaid = $payable && in_array($paymentMethod, ['cash', 'bank_transfer'], true);

        try {
            $newBookings = DB::transaction(function () use (
                $event,
                $payload,
                $useOfflinePaid,
                $paymentMethod,
                $offlineReference,
            ) {
                $addonError = AdditionalServiceInventory::decrement($event, $payload);
                if ($addonError !== null) {
                    throw new \RuntimeException($addonError);
                }

                return EventBooking::createManyFromCartPayload($event, $payload, [
                    'user_id' => $payload['user_id'] ?? null,
                    'attendee_name' => $payload['attendee_name'],
                    'email' => $payload['email'],
                    'phone' => $payload['phone'] ?? null,
                    'status' => $useOfflinePaid ? 'pending_offline_payment' : 'confirmed',
                    'stripe_checkout_session_id' => null,
                    'paypal_order_id' => null,
                    'razorpay_payment_id' => null,
                    'sslcommerz_val_id' => null,
                    'offline_payment_method' => $useOfflinePaid ? $paymentMethod : null,
                    'offline_payment_reference' => $useOfflinePaid ? ($offlineReference !== '' ? $offlineReference : null) : null,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('book_error', $e->getMessage());
        }

        $newBookingIds = $newBookings->pluck('id')->all();

        if ($newBookings->isNotEmpty() && ($newBookings->first()?->status === 'confirmed')) {
            $bookingConfirmationNotifier->notify($event, $newBookings);
        }

        if ($useOfflinePaid && $newBookings->isNotEmpty()) {
            $paymentLabel = $paymentMethod === 'cash' ? 'cash payment' : 'bank transfer';
            $sessionMessage = 'Thank you — your booking was received as pending payment. We will confirm it after verifying your '.$paymentLabel.'.';
        } else {
            $sessionMessage = 'Thank you — your tickets are reserved.';
        }

        if ($registeredViaBookingSignup && $newBookings->isNotEmpty()) {
            if ($useOfflinePaid) {
                $paymentLabelLong = $paymentMethod === 'cash' ? 'cash payment' : 'bank transfer';

                return BookingThankYouSession::redirect(
                    $request,
                    $event,
                    $newBookings,
                    'Thank you — your booking was received as pending payment. We will confirm it after verifying your '.$paymentLabelLong.'. You are signed in — you can track it under My account.',
                    true,
                );
            }

            return BookingThankYouSession::redirect(
                $request,
                $event,
                $newBookings,
                'Thank you — your tickets are reserved and you are signed in. You can view this order under My account.',
                true,
            );
        }

        return BookingThankYouSession::redirect($request, $event, $newBookings, $sessionMessage);
    }
}
