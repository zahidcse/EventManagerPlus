<?php

declare(strict_types=1);

namespace App\Services\ReportAi;

use App\Enums\ReportAiVendor;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

/**
 * Resolved connection for one HTTP request — prefers admin {@see SiteSetting} overrides, falls back to .env / report_ai.php.
 */
final class ReportAiRuntimeSettings
{
    private function __construct(
        public bool $effectiveEnabled,
        public ReportAiVendor $vendor,
        public string $apiKey,
        public string $resolvedModel,
        public int $timeoutSeconds,
        public ?string $baseUrlOverride,
    ) {}

    public static function resolve(): self
    {
        /** @phpstan-ignore-next-line */
        $timeout = (int) Config::get('report_ai.timeout', 60);

        $envEnabled = filter_var(Config::get('report_ai.enabled', false), FILTER_VALIDATE_BOOLEAN);
        $envKey = (string) Config::get('report_ai.openai_api_key', '');

        try {
            if (! Schema::hasTable('site_settings') || ! Schema::hasColumn('site_settings', 'report_ai_enabled')) {
                /** @phpstan-ignore-next-line */
                return self::fallbackFromEnv(envEnabled: $envEnabled, envKey: $envKey, timeout: $timeout);
            }
        } catch (\Throwable) {
            return self::fallbackFromEnv(envEnabled: $envEnabled, envKey: $envKey, timeout: $timeout);
        }

        $row = SiteSetting::query()->first();
        if ($row === null) {
            return self::fallbackFromEnv(envEnabled: $envEnabled, envKey: $envKey, timeout: $timeout);
        }

        $effectiveEnabled = filter_var((bool) $row->report_ai_enabled, FILTER_VALIDATE_BOOLEAN);

        $vendor = ReportAiVendor::tryFrom((string) ($row->report_ai_provider ?? '')) ?? ReportAiVendor::OpenAi;

        $storedKeyRaw = isset($row->report_ai_api_key) ? trim((string) $row->report_ai_api_key) : '';
        $apiKey = $storedKeyRaw !== '' ? $storedKeyRaw : $envKey;

        $modelStored = isset($row->report_ai_model) ? trim((string) $row->report_ai_model) : '';
        $resolvedModel = $modelStored !== '' ? $modelStored : $vendor->defaultModel();

        $overrideRaw = isset($row->report_ai_api_base_url_override)
            ? trim((string) $row->report_ai_api_base_url_override)
            : '';
        $override = $overrideRaw !== '' ? rtrim($overrideRaw, '/') : null;

        return new self(
            effectiveEnabled: $effectiveEnabled,
            vendor: $vendor,
            apiKey: $apiKey,
            resolvedModel: $resolvedModel,
            timeoutSeconds: $timeout,
            baseUrlOverride: $override,
        );
    }

    public function isUsable(): bool
    {
        return $this->effectiveEnabled && $this->apiKey !== '';
    }

    public function unusableReason(): string
    {
        if (! $this->effectiveEnabled) {
            return 'Turn on AI reporting under Settings → AI reports, or set REPORT_AI_ENABLED=true in .env for legacy setups.';
        }

        if ($this->apiKey === '') {
            return 'Add your API key under Settings → AI reports (Anthropic/Google/OpenAI-compatible), or set OPENAI_API_KEY in .env for legacy setups.';
        }

        return '';
    }

    /** Legacy fallback when migrations have not applied yet — uses .env / config/report_ai.php. */
    private static function fallbackFromEnv(bool $envEnabled, string $envKey, int $timeout): self
    {
        $vendor = ReportAiVendor::OpenAi;

        return new self(
            effectiveEnabled: $envEnabled,
            vendor: $vendor,
            apiKey: trim($envKey),
            resolvedModel: trim((string) Config::get('report_ai.model', $vendor->defaultModel())) ?: $vendor->defaultModel(),
            timeoutSeconds: $timeout,
            baseUrlOverride: (function (): ?string {
                $base = trim((string) Config::get('report_ai.openai_base_url', ''));
                /** @phpstan-ignore-next-line */
                return $base !== '' ? rtrim($base, '/') : null;
            })(),
        );
    }
}
