<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PayPal REST v2 (Orders) for one-time capture checkout.
 */
final class PayPalCheckoutApi
{
    public function __construct(
        private readonly SiteSetting $setting
    ) {}

    public function baseUrl(): string
    {
        return $this->setting->paypal_mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * @return array<string, mixed>|null Decoded JSON or null on failure
     */
    public function createOrder(
        string $accessToken,
        string $returnUrl,
        string $cancelUrl,
        string $currencyUpper,
        string $valueDecimal,
        string $checkoutId,
        string $brandName
    ): ?array {
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'custom_id' => $checkoutId,
                    'description' => 'Event tickets',
                    'amount' => [
                        'currency_code' => $currencyUpper,
                        'value' => $valueDecimal,
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => mb_substr($brandName, 0, 127),
                'locale' => 'en-US',
                'landing_page' => 'NO_PREFERENCE',
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ];

        $response = $this->postJson('/v2/checkout/orders', $accessToken, $payload);
        if (! $response->successful()) {
            Log::warning('PayPal create order failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getOrder(string $accessToken, string $orderId): ?array
    {
        $response = $this->getJson('/v2/checkout/orders/'.rawurlencode($orderId), $accessToken);
        if (! $response->successful()) {
            Log::warning('PayPal get order failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>|null Captured order JSON
     */
    public function captureOrder(string $accessToken, string $orderId): ?array
    {
        $response = $this->postJson('/v2/checkout/orders/'.rawurlencode($orderId).'/capture', $accessToken, []);

        if (! $response->successful()) {
            Log::warning('PayPal capture failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    public function getAccessToken(): ?string
    {
        $clientId = $this->setting->paypal_client_id;
        $secret = $this->setting->paypal_secret;
        if ($clientId === null || $clientId === '' || $secret === null || $secret === '') {
            return null;
        }

        $cacheKey = 'paypal_oauth:'.hash('sha256', (string) $this->setting->id.'|'.$clientId.'|'.$this->setting->paypal_mode);

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $response = $this->paypalPendingRequest()
                ->withBasicAuth($clientId, $secret)
                ->asForm()
                ->post($this->baseUrl().'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);
        } catch (Throwable $e) {
            Log::error('PayPal OAuth request failed', ['e' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('PayPal OAuth failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $token = $response->json('access_token');
        if (! is_string($token) || $token === '') {
            return null;
        }

        Cache::put($cacheKey, $token, 240);

        return $token;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    public static function approveUrlFromOrderJson(?array $json): string
    {
        if ($json === null) {
            return '';
        }
        foreach ($json['links'] ?? [] as $link) {
            if (! is_array($link)) {
                continue;
            }
            if (($link['rel'] ?? '') === 'approve' && isset($link['href']) && is_string($link['href'])) {
                return $link['href'];
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $orderAfterCapture
     * @return array{0: int, 1: string}|null [cents, currency upper]
     */
    public static function capturedAmount(?array $orderAfterCapture): ?array
    {
        if ($orderAfterCapture === null) {
            return null;
        }
        $units = $orderAfterCapture['purchase_units'] ?? [];
        if (! is_array($units) || $units === []) {
            return null;
        }
        $first = $units[0];
        if (! is_array($first)) {
            return null;
        }
        $payments = $first['payments'] ?? [];
        if (! is_array($payments)) {
            return null;
        }
        $captures = $payments['captures'] ?? [];
        if (! is_array($captures) || $captures === []) {
            return null;
        }
        $cap = $captures[0];
        if (! is_array($cap)) {
            return null;
        }
        $amount = $cap['amount'] ?? null;
        if (! is_array($amount)) {
            return null;
        }
        $value = $amount['value'] ?? null;
        $cur = $amount['currency_code'] ?? null;
        if (! is_string($value) || ! is_string($cur)) {
            return null;
        }

        return [(int) round((float) $value * 100), strtoupper($cur)];
    }

    private function paypalPendingRequest(): PendingRequest
    {
        $options = [];
        $bundle = config('services.paypal.ca_bundle');
        if (is_string($bundle) && $bundle !== '' && is_file($bundle)) {
            $options['verify'] = $bundle;
        } elseif ((bool) config('services.paypal.verify_ssl', true) === false) {
            $options['verify'] = false;
        }

        $request = Http::acceptJson();

        return $options === [] ? $request : $request->withOptions($options);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postJson(string $path, string $accessToken, array $payload): Response
    {
        return $this->paypalPendingRequest()->withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->acceptJson()->post($this->baseUrl().$path, $payload);
    }

    private function getJson(string $path, string $accessToken): Response
    {
        return $this->paypalPendingRequest()->withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
        ])->acceptJson()->get($this->baseUrl().$path);
    }
}
