<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReportAiVendor;
use App\Models\SiteSetting;
use App\Support\Edition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $enc = $this->input('smtp_encryption');
        $merge = [
            'stripe_enabled' => $this->boolean('stripe_enabled'),
            'paypal_enabled' => $this->boolean('paypal_enabled'),
            'razorpay_enabled' => $this->boolean('razorpay_enabled'),
            'sslcommerz_enabled' => $this->boolean('sslcommerz_enabled'),
            'payment_cash_enabled' => $this->boolean('payment_cash_enabled'),
            'payment_bank_transfer_enabled' => $this->boolean('payment_bank_transfer_enabled'),
            'seat_plan_enabled' => $this->boolean('seat_plan_enabled'),
            'report_ai_enabled' => $this->boolean('report_ai_enabled'),
            'clear_frontend_hero' => $this->boolean('clear_frontend_hero'),
            'clear_admin_logo' => $this->boolean('clear_admin_logo'),
            'clear_favicon' => $this->boolean('clear_favicon'),
            'smtp_encryption' => $enc === '' || $enc === null ? null : $enc,
        ];
        foreach (['contact_email', 'smtp_from_address', 'footer_copyright_text', 'home_meta_title', 'home_meta_description'] as $key) {
            if ($this->input($key) === '') {
                $merge[$key] = null;
            }
        }
        foreach (['social_facebook_url', 'social_twitter_url', 'social_instagram_url', 'social_youtube_url', 'social_linkedin_url'] as $key) {
            if ($this->input($key) === '') {
                $merge[$key] = null;
            }
        }

        $allowedSocialKeys = SiteSetting::socialPlatformKeys();
        $rawOrder = $this->input('social_media_order');
        if (is_array($rawOrder)) {
            $normalizedOrder = [];
            foreach ($rawOrder as $key) {
                if (is_string($key) && in_array($key, $allowedSocialKeys, true) && ! in_array($key, $normalizedOrder, true)) {
                    $normalizedOrder[] = $key;
                }
            }
            foreach ($allowedSocialKeys as $key) {
                if (! in_array($key, $normalizedOrder, true)) {
                    $normalizedOrder[] = $key;
                }
            }
            $merge['social_media_order'] = $normalizedOrder;
        }

        $this->merge($merge);

        if (Edition::isFree()) {
            $theme = (string) $this->input('frontend_theme', Edition::allowedThemes()[0] ?? 'default');
            if (! Edition::allowsTheme($theme)) {
                $theme = Edition::allowedThemes()[0] ?? 'default';
            }

            $this->merge([
                'frontend_theme' => $theme,
                'seat_plan_enabled' => false,
                'paypal_enabled' => false,
                'razorpay_enabled' => false,
                'sslcommerz_enabled' => false,
                'payment_cash_enabled' => false,
                'payment_bank_transfer_enabled' => false,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'site_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'social_facebook_url' => ['nullable', 'url', 'max:500'],
            'social_twitter_url' => ['nullable', 'url', 'max:500'],
            'social_instagram_url' => ['nullable', 'url', 'max:500'],
            'social_youtube_url' => ['nullable', 'url', 'max:500'],
            'social_linkedin_url' => ['nullable', 'url', 'max:500'],
            'social_media_order' => ['nullable', 'array', 'max:10'],
            'social_media_order.*' => ['string', Rule::in(SiteSetting::socialPlatformKeys())],
            'footer_copyright_text' => ['nullable', 'string', 'max:500'],
            'frontend_theme' => ['required', Rule::in(['default', 'classic', 'classic-light'])],
            'logo' => ['nullable', 'image', 'max:4096'],
            'admin_logo' => ['nullable', 'image', 'max:4096'],
            'frontend_hero_image' => ['nullable', 'image', 'max:8192'],
            'clear_frontend_hero' => ['boolean'],
            'clear_admin_logo' => ['boolean'],
            'favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,ico,svg', 'max:1024'],
            'clear_favicon' => ['boolean'],

            'seat_plan_enabled' => ['boolean'],

            'home_faqs' => ['nullable', 'array', 'max:50'],
            'home_faqs.*.question' => ['nullable', 'string', 'max:500'],
            'home_faqs.*.answer' => ['nullable', 'string', 'max:20000'],

            'home_hero_badge' => ['nullable', 'string', 'max:255'],
            'home_hero_headline_before' => ['nullable', 'string', 'max:255'],
            'home_hero_headline_highlight' => ['nullable', 'string', 'max:255'],
            'home_hero_headline_suffix' => ['nullable', 'string', 'max:255'],
            'home_hero_lead' => ['nullable', 'string', 'max:2000'],
            'home_hero_cta_primary_label' => ['nullable', 'string', 'max:128'],
            'home_hero_cta_secondary_label' => ['nullable', 'string', 'max:128'],
            'home_hero_stat_1_label' => ['nullable', 'string', 'max:128'],
            'home_hero_stat_2_value' => ['nullable', 'string', 'max:64'],
            'home_hero_stat_2_label' => ['nullable', 'string', 'max:128'],
            'home_hero_stat_3_value' => ['nullable', 'string', 'max:64'],
            'home_hero_stat_3_label' => ['nullable', 'string', 'max:128'],
            'home_how_eyebrow' => ['nullable', 'string', 'max:128'],
            'home_how_title_before' => ['nullable', 'string', 'max:255'],
            'home_how_title_highlight' => ['nullable', 'string', 'max:255'],
            'home_how_step1_title' => ['nullable', 'string', 'max:128'],
            'home_how_step1_description' => ['nullable', 'string', 'max:2000'],
            'home_how_step2_title' => ['nullable', 'string', 'max:128'],
            'home_how_step2_description' => ['nullable', 'string', 'max:2000'],
            'home_how_step3_title' => ['nullable', 'string', 'max:128'],
            'home_how_step3_description' => ['nullable', 'string', 'max:2000'],
            'home_faq_eyebrow' => ['nullable', 'string', 'max:128'],
            'home_faq_title_before' => ['nullable', 'string', 'max:255'],
            'home_faq_title_highlight' => ['nullable', 'string', 'max:255'],

            'home_meta_title' => ['nullable', 'string', 'max:255'],
            'home_meta_description' => ['nullable', 'string', 'max:2000'],
            'home_contact_eyebrow' => ['nullable', 'string', 'max:128'],
            'home_contact_title_before' => ['nullable', 'string', 'max:255'],
            'home_contact_title_highlight' => ['nullable', 'string', 'max:255'],
            'home_contact_lead' => ['nullable', 'string', 'max:2000'],

            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:500'],
            'smtp_encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
            'smtp_from_address' => ['nullable', 'email', 'max:255'],
            'smtp_from_name' => ['nullable', 'string', 'max:255'],

            'stripe_enabled' => ['boolean'],
            'stripe_public_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:500'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:500'],

            'paypal_enabled' => ['boolean'],
            'paypal_client_id' => ['nullable', 'string', 'max:255'],
            'paypal_secret' => ['nullable', 'string', 'max:500'],
            'paypal_mode' => ['nullable', Rule::in(['sandbox', 'live'])],

            'razorpay_enabled' => ['boolean'],
            'razorpay_key_id' => ['nullable', 'string', 'max:255'],
            'razorpay_key_secret' => ['nullable', 'string', 'max:500'],

            'sslcommerz_enabled' => ['boolean'],
            'sslcommerz_store_id' => ['nullable', 'string', 'max:64'],
            'sslcommerz_store_password' => ['nullable', 'string', 'max:500'],
            'sslcommerz_mode' => ['nullable', Rule::in(['sandbox', 'live'])],

            'payment_cash_enabled' => ['boolean'],
            'payment_bank_transfer_enabled' => ['boolean'],
            'bank_transfer_instructions' => ['nullable', 'string', 'max:20000'],

            'report_ai_enabled' => ['boolean'],
            'report_ai_provider' => ['nullable', Rule::enum(ReportAiVendor::class)],
            'report_ai_model' => ['nullable', 'string', 'max:128'],
            'report_ai_api_base_url_override' => ['nullable', 'string', 'max:2048'],
            'report_ai_api_key' => ['nullable', 'string', 'max:8192'],
        ];
    }
}
