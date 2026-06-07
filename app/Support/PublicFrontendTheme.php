<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;

final class PublicFrontendTheme
{
  /** @var list<string> */
  public const CLASSIC_FAMILY = ['classic', 'classic-light'];

  public const CLASSIC_LIGHT_DEFAULT_LOGO = 'themes/classic-light/light_logo.png';

  public const CLASSIC_LIGHT_DEFAULT_HERO = 'themes/classic-light/light-hero-uubOEL6T.jpg';

  private const DEFAULT_HERO_UNSPLASH = 'https://images.unsplash.com/photo-1540039155733-5bb30b53aa88?w=1920&q=80';

  /**
   * Admin → Settings theme picker (label + optional preview under public/).
   *
   * @return array<string, array{label: string, description: string, preview: ?string}>
   */
  public static function adminThemeOptions(): array
  {
    return [
      'default' => [
        'label' => 'Default',
        'description' => 'Minimal landing',
        'preview' => null,
      ],
      'classic' => [
        'label' => 'Classic',
        'description' => '',
        'preview' => 'themes/classic/preview.png',
      ],
      'classic-light' => [
        'label' => 'Classic Light',
        'description' => '',
        'preview' => 'themes/classic-light/preview.png',
      ],
    ];
  }

  /**
   * Matches Admin → Settings → public theme.
   */
  public static function resolvedKey(): string
  {
    if (!Schema::hasTable('site_settings')) {
      return 'default';
    }

    $setting = SiteSetting::instance();

    $themeKey = Schema::hasColumn('site_settings', 'frontend_theme')
      ? (string) $setting->frontend_theme
      : 'default';

    $themeKey = Edition::resolveThemeKey($themeKey);

    if (in_array($themeKey, self::CLASSIC_FAMILY, true)) {
      return $themeKey;
    }

    return 'default';
  }

  public static function isClassicFamily(): bool
  {
    return in_array(self::resolvedKey(), self::CLASSIC_FAMILY, true);
  }

  public static function isClassicLight(): bool
  {
    return self::resolvedKey() === 'classic-light';
  }

  public static function isClassicDark(): bool
  {
    return self::resolvedKey() === 'classic';
  }

  /**
   * Public site logo: admin upload, else Classic Light bundled default when that theme is active.
   */
  public static function resolvePublicLogoUrl(?SiteSetting $setting = null): ?string
  {
    if ($setting === null && Schema::hasTable('site_settings')) {
      $setting = SiteSetting::instance();
    }

    if ($setting && filled($setting->logo_path)) {
      return $setting->logoPublicUrl();
    }

    if (self::isClassicLight()) {
      return asset(self::CLASSIC_LIGHT_DEFAULT_LOGO);
    }

    return null;
  }

  /**
   * Classic homepage hero: admin upload, else Classic Light bundled default, else stock photo.
   */
  public static function resolveHeroImageUrl(?SiteSetting $setting = null): string
  {
    if ($setting === null && Schema::hasTable('site_settings')) {
      $setting = SiteSetting::instance();
    }

    if ($setting && Schema::hasColumn('site_settings', 'frontend_hero_image_path')) {
      $custom = $setting->classicHeroPublicUrl();
      if ($custom) {
        return $custom;
      }
    }

    if (self::isClassicLight()) {
      return asset(self::CLASSIC_LIGHT_DEFAULT_HERO);
    }

    return self::DEFAULT_HERO_UNSPLASH;
  }

  public static function stylesheet(): string
  {
    return match (self::resolvedKey()) {
      'classic-light' => 'themes/classic-light/styles.css',
      'classic' => 'themes/classic/styles.css',
      default => '',
    };
  }

  /** Classic and Classic Light share the same layout class; colors come from the theme stylesheet. */
  public static function layoutThemeClass(): string
  {
    return self::isClassicFamily() ? 'ep-theme-classic' : 'ep-theme-default';
  }

  public static function bodyClass(): string
  {
    return match (self::resolvedKey()) {
      'classic-light' => 'classic-light-site',
      'classic' => 'classic-site',
      default => 'default-site',
    };
  }

  /**
   * Theme-scoped event views: public.classic.events.* vs public.events.default.*
   */
  public static function eventView(string $page): string
  {
    return self::isClassicFamily()
      ? 'public.classic.events.' . $page
      : 'public.events.default.' . $page;
  }

  /** Theme-scoped blog views: public.classic.blog.* vs public.blog.default.* */
  public static function blogView(string $page): string
  {
    return self::isClassicFamily()
      ? 'public.classic.blog.' . $page
      : 'public.blog.default.' . $page;
  }

  /** Theme-scoped CMS pages: public.classic.pages.* vs public.pages.default.* */
  public static function pageView(string $page): string
  {
    return self::isClassicFamily()
      ? 'public.classic.pages.' . $page
      : 'public.pages.default.' . $page;
  }

  /**
   * @return array<string, mixed>
   */
  public static function publicPageExtras(): array
  {
    if (!Schema::hasTable('site_settings')) {
      return [
        'contactEmail' => null,
        'contactPhone' => null,
        'heroImageUrl' => self::resolveHeroImageUrl(),
      ];
    }

    $setting = SiteSetting::instance();

    return [
      'contactEmail' => $setting->contact_email,
      'contactPhone' => $setting->contact_phone,
      'heroImageUrl' => self::resolveHeroImageUrl($setting),
    ];
  }
}
