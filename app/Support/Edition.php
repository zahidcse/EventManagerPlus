<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Free-edition feature limits for the open-source release.
 * Premium features are preview-only in the admin UI; unlock them via Event Manager Plus.
 */
final class Edition
{
    public static function isFree(): bool
    {
        return true;
    }

    public static function premiumMessage(): string
    {
        return (string) config('edition.premium_message', 'Available in premium version');
    }

    public static function premiumUrl(): string
    {
        return (string) config('edition.premium_url', 'https://lucrativeit.com/products/event-manager-plus');
    }

    /**
     * @return list<string>
     */
    public static function allowedThemes(): array
    {
        return config('edition.themes', ['default']);
    }

    public static function allowsTheme(string $themeKey): bool
    {
        return in_array($themeKey, self::allowedThemes(), true);
    }

    public static function allowsRecurringSchedule(): bool
    {
        return in_array('recurring', config('edition.schedule_types', ['single']), true)
            || in_array('custom_interval', config('edition.schedule_types', ['single']), true);
    }

    public static function allowsAdditionalServices(): bool
    {
        return (bool) config('edition.additional_services', false);
    }

    public static function allowsEarlyBirdPricing(): bool
    {
        return (bool) config('edition.early_bird_pricing', false);
    }

    public static function allowsPdfTicketSettings(): bool
    {
        return false;
    }

    public static function allowsAttendeeFormSettings(): bool
    {
        return false;
    }

    public static function allowsPaymentGateway(string $gateway): bool
    {
        return in_array($gateway, config('edition.payment_gateways', ['stripe']), true);
    }

    /**
     * Resolve the active public theme key, respecting edition limits.
     */
    public static function resolveThemeKey(string $storedTheme): string
    {
        if (self::allowsTheme($storedTheme)) {
            return $storedTheme;
        }

        return self::allowedThemes()[0] ?? 'default';
    }
}
