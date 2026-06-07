<?php

namespace App\Models;

use App\Casts\LegacyCompatibleEncryptedString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'contact_email',
        'contact_phone',
        'social_facebook_url',
        'social_twitter_url',
        'social_instagram_url',
        'social_youtube_url',
        'social_linkedin_url',
        'social_media_order',
        'footer_copyright_text',
        'logo_path',
        'favicon_path',
        'admin_logo_path',
        'frontend_theme',
        'frontend_hero_image_path',
        'home_hero_badge',
        'home_hero_headline_before',
        'home_hero_headline_highlight',
        'home_hero_headline_suffix',
        'home_hero_lead',
        'home_hero_cta_primary_label',
        'home_hero_cta_secondary_label',
        'home_hero_stat_1_label',
        'home_hero_stat_2_value',
        'home_hero_stat_2_label',
        'home_hero_stat_3_value',
        'home_hero_stat_3_label',
        'home_how_eyebrow',
        'home_how_title_before',
        'home_how_title_highlight',
        'home_how_step1_title',
        'home_how_step1_description',
        'home_how_step2_title',
        'home_how_step2_description',
        'home_how_step3_title',
        'home_how_step3_description',
        'home_faq_eyebrow',
        'home_faq_title_before',
        'home_faq_title_highlight',
        'home_meta_title',
        'home_meta_description',
        'home_contact_eyebrow',
        'home_contact_title_before',
        'home_contact_title_highlight',
        'home_contact_lead',
        'seat_plan_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name',
        'stripe_enabled',
        'stripe_public_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'paypal_enabled',
        'paypal_client_id',
        'paypal_secret',
        'paypal_mode',
        'razorpay_enabled',
        'razorpay_key_id',
        'razorpay_key_secret',
        'sslcommerz_enabled',
        'sslcommerz_store_id',
        'sslcommerz_store_password',
        'sslcommerz_mode',
        'payment_cash_enabled',
        'payment_bank_transfer_enabled',
        'bank_transfer_instructions',
        'report_ai_enabled',
        'report_ai_provider',
        'report_ai_model',
        'report_ai_api_base_url_override',
        'report_ai_api_key',
    ];

    protected function casts(): array
    {
        return [
            'smtp_port' => 'integer',
            'stripe_enabled' => 'boolean',
            'paypal_enabled' => 'boolean',
            'razorpay_enabled' => 'boolean',
            'sslcommerz_enabled' => 'boolean',
            'payment_cash_enabled' => 'boolean',
            'payment_bank_transfer_enabled' => 'boolean',
            'seat_plan_enabled' => 'boolean',
            'report_ai_enabled' => 'boolean',
            'smtp_password' => LegacyCompatibleEncryptedString::class,
            'stripe_secret_key' => LegacyCompatibleEncryptedString::class,
            'stripe_webhook_secret' => LegacyCompatibleEncryptedString::class,
            'paypal_secret' => LegacyCompatibleEncryptedString::class,
            'razorpay_key_secret' => LegacyCompatibleEncryptedString::class,
            'sslcommerz_store_password' => LegacyCompatibleEncryptedString::class,
            'report_ai_api_key' => LegacyCompatibleEncryptedString::class,
            'social_media_order' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    public static function socialPlatformKeys(): array
    {
        return ['facebook', 'twitter', 'instagram', 'youtube', 'linkedin'];
    }

    /**
     * @return list<string>
     */
    public static function defaultSocialMediaOrder(): array
    {
        return self::socialPlatformKeys();
    }

    /**
     * @return array<string, array{field: string, icon: string, label: string}>
     */
    public static function socialPlatformDefinitions(): array
    {
        return [
            'facebook' => ['field' => 'social_facebook_url', 'icon' => 'facebook', 'label' => 'Facebook'],
            'twitter' => ['field' => 'social_twitter_url', 'icon' => 'twitter', 'label' => 'Twitter / X'],
            'instagram' => ['field' => 'social_instagram_url', 'icon' => 'instagram', 'label' => 'Instagram'],
            'youtube' => ['field' => 'social_youtube_url', 'icon' => 'youtube', 'label' => 'YouTube'],
            'linkedin' => ['field' => 'social_linkedin_url', 'icon' => 'linkedin', 'label' => 'LinkedIn'],
        ];
    }

    /**
     * @return list<string>
     */
    public function socialMediaOrderResolved(): array
    {
        $allowed = self::socialPlatformKeys();
        $saved = is_array($this->social_media_order) ? $this->social_media_order : [];
        $order = [];

        foreach ($saved as $key) {
            if (is_string($key) && in_array($key, $allowed, true) && ! in_array($key, $order, true)) {
                $order[] = $key;
            }
        }

        foreach ($allowed as $key) {
            if (! in_array($key, $order, true)) {
                $order[] = $key;
            }
        }

        return $order;
    }

    public static function seatPlanEnabled(): bool
    {
        return false;
    }

    public static function instance(): self
    {
        $row = static::query()->first();
        if ($row === null) {
            $row = static::query()->create([
                'site_name' => config('app.name', 'Event Manager'),
                'frontend_theme' => 'default',
                'paypal_mode' => 'sandbox',
            ]);
        }

        return $row;
    }

    public function logoPublicUrl(): ?string
    {
        return $this->logo_path ? asset('uploads/'.$this->logo_path) : null;
    }

    public function faviconPublicUrl(): ?string
    {
        return filled($this->favicon_path) ? asset('uploads/'.$this->favicon_path) : null;
    }

    public function adminLogoPublicUrl(): ?string
    {
        return filled($this->admin_logo_path) ? asset('uploads/'.$this->admin_logo_path) : null;
    }

    /**
     * Logo shown on admin login and sidebar: dedicated admin logo, else public site logo.
     */
    public function adminBrandLogoUrl(): ?string
    {
        return $this->adminLogoPublicUrl() ?? $this->logoPublicUrl();
    }

    public function classicHeroPublicUrl(): ?string
    {
        return $this->frontend_hero_image_path ? asset('uploads/'.$this->frontend_hero_image_path) : null;
    }

    /**
     * @return array<string, string>
     */
    public static function homeContentDefaults(): array
    {
        return [
            'home_hero_badge' => 'Discover. Book. Experience.',
            'home_hero_headline_before' => 'Find your next',
            'home_hero_headline_highlight' => 'unforgettable',
            'home_hero_headline_suffix' => 'moment',
            'home_hero_lead' => 'Concerts, conferences, festivals & nightlife — book tickets to exciting events in seconds.',
            'home_hero_cta_primary_label' => 'Browse Events',
            'home_hero_cta_secondary_label' => 'How it works',
            'home_hero_stat_1_label' => 'Upcoming listed',
            'home_hero_stat_2_value' => 'All',
            'home_hero_stat_2_label' => 'Experience types',
            'home_hero_stat_3_value' => '24/7',
            'home_hero_stat_3_label' => 'Support ready',
            'home_how_eyebrow' => 'How it works',
            'home_how_title_before' => 'Three steps to your',
            'home_how_title_highlight' => 'next experience',
            'home_how_step1_title' => 'Discover',
            'home_how_step1_description' => 'Browse events by genre, city, or date — curated for you.',
            'home_how_step2_title' => 'Book',
            'home_how_step2_description' => 'Pick tickets and check out securely online.',
            'home_how_step3_title' => 'Enjoy',
            'home_how_step3_description' => 'Get confirmation details and enjoy the show.',
            'home_faq_eyebrow' => 'FAQ',
            'home_faq_title_before' => 'Questions?',
            'home_faq_title_highlight' => 'Answered.',
            'home_contact_eyebrow' => 'Contact us',
            'home_contact_title_before' => 'Need a hand?',
            'home_contact_title_highlight' => "We're here for you.",
            'home_contact_lead' => 'Reach out using the details below or send a quick message — we\'ll route it to the right team.',
        ];
    }

    public function homeMetaTitle(?string $siteName = null): string
    {
        $custom = trim((string) ($this->home_meta_title ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        $name = trim((string) ($siteName ?? $this->site_name ?? ''));
        if ($name === '') {
            $name = (string) config('app.name', 'Event Manager');
        }

        return $name.' — Book tickets to unforgettable events';
    }

    public function homeMetaDescription(): string
    {
        $custom = trim((string) ($this->home_meta_description ?? ''));

        return $custom !== ''
            ? $custom
            : 'Discover and book tickets to concerts, festivals, conferences and more.';
    }

    public function homeField(string $column): string
    {
        $defaults = self::homeContentDefaults();
        $value = trim((string) ($this->{$column} ?? ''));

        return $value !== '' ? $value : ($defaults[$column] ?? '');
    }

    public function footerCopyrightLine(?string $siteName = null): string
    {
        $resolvedName = trim((string) ($siteName ?? $this->site_name ?? ''));
        if ($resolvedName === '') {
            $resolvedName = (string) config('app.name', 'Event Manager');
        }

        $template = trim((string) ($this->footer_copyright_text ?? ''));
        if ($template === '') {
            $template = '© {year} {site_name}. All rights reserved.';
        }

        return str_replace(
            ['{year}', '{site_name}'],
            [(string) date('Y'), $resolvedName],
            $template,
        );
    }

    /**
     * Social profiles for the public footer (only non-empty URLs).
     *
     * @return list<array{url: string, icon: string, label: string}>
     */
    public function footerSocialLinks(): array
    {
        $definitions = self::socialPlatformDefinitions();
        $links = [];

        foreach ($this->socialMediaOrderResolved() as $key) {
            $definition = $definitions[$key] ?? null;
            if ($definition === null) {
                continue;
            }

            $url = trim((string) ($this->{$definition['field']} ?? ''));
            if ($url === '') {
                continue;
            }

            $links[] = [
                'url' => $url,
                'icon' => $definition['icon'],
                'label' => $definition['label'],
            ];
        }

        return $links;
    }
}
