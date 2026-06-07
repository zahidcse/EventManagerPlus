<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SSLCommerz hosted checkout (Bangladesh): session API + order validation API.
 *
 * @see https://developer.sslcommerz.com/doc/v4/
 */
final class SslCommerzGateway
{
    public function __construct(
        private readonly SiteSetting $setting,
    ) {}

    private function baseRoot(): string
    {
        return $this->setting->sslcommerz_mode === 'live'
            ? 'https://securepay.sslcommerz.com'
            : 'https://sandbox.sslcommerz.com';
    }

    /**
     * Start hosted session — returns GatewayPage URL or null on failure.
     *
     * @param  array<string, string|int|float|null>  $fields
     */
    public function createHostedGatewayUrl(array $fields): ?string
    {
        try {
            $response = Http::timeout(45)
                ->asForm()
                ->post($this->baseRoot().'/gwprocess/v4/api.php', $fields);
        } catch (\Throwable $e) {
            Log::error('SSLCommerz initiate request failed', ['exception' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('SSLCommerz initiate non-HTTP-2xx', ['status' => $response->status()]);

            return null;
        }

        $decoded = json_decode($response->body(), true);

        if (! is_array($decoded)) {
            Log::warning('SSLCommerz initiate invalid JSON');

            return null;
        }

        $status = (string) ($decoded['status'] ?? '');
        if ($status !== 'SUCCESS') {
            Log::warning('SSLCommerz initiate declined', [
                'failedreason' => $decoded['failedreason'] ?? '',
            ]);

            return null;
        }

        $url = $decoded['GatewayPageURL'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function validateByValId(string $valId): ?array
    {
        try {
            $response = Http::timeout(45)->get(
                $this->baseRoot().'/validator/api/validationserverAPI.php',
                [
                    'val_id' => $valId,
                    'store_id' => (string) $this->setting->sslcommerz_store_id,
                    'store_passwd' => (string) $this->setting->sslcommerz_store_password,
                    'format' => 'json',
                    'v' => '1',
                ]
            );
        } catch (\Throwable $e) {
            Log::error('SSLCommerz validation request failed', ['exception' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('SSLCommerz validation non-HTTP-2xx', ['status' => $response->status()]);

            return null;
        }

        $decoded = json_decode($response->body(), true);

        return is_array($decoded) ? $decoded : null;
    }

    /** Minor units matching {@see BookingOrderTotals} (stripe-style cents/poisha/etc.). */
    public static function decimalToMinor(string $decimalLike): int
    {
        $normalized = str_replace(',', '', trim($decimalLike));

        return (int) round((float) $normalized * 100);
    }
}
