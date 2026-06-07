<?php

namespace App\Providers;

use App\Http\Middleware\EnsureIsAdmin;
use App\Models\Page;
use App\Models\SiteHomeFaq;
use App\Models\SiteSetting;
use App\Services\AdminBookingNotificationService;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Support\Edition;
use App\Support\Installer\InstallationStatus;
use App\Support\PublicFrontendTheme;
use App\Repositories\Contracts\OrganizerRepositoryInterface;
use App\Repositories\EloquentEventRepository;
use App\Repositories\EloquentOrganizerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OrganizerRepositoryInterface::class, EloquentOrganizerRepository::class);
        $this->app->bind(EventRepositoryInterface::class, EloquentEventRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::aliasMiddleware('admin', EnsureIsAdmin::class);

        View::share([
            'editionIsFree' => Edition::isFree(),
            'editionPremiumMessage' => Edition::premiumMessage(),
            'editionPremiumUrl' => Edition::premiumUrl(),
        ]);

        $this->configureInstallerSessionsAndCache();

        $this->forcePublicUrlRootFromRequestWhenLocal();

        $this->configureMailFromDatabase();

        View::composer('admin.settings.index', function ($view): void {
            $homeFaqs = collect();
            try {
                if (Schema::hasTable('site_home_faqs')) {
                    $homeFaqs = SiteHomeFaq::query()->orderBy('sort_order')->orderBy('id')->get();
                }
            } catch (\Throwable) {
            }
            $view->with('homeFaqs', $homeFaqs);
        });

        View::composer(
            [
                'admin.layouts.app',
                'admin.layouts.guest',
                'admin.settings.index',
                'admin.events.content',
                'admin.chunks._event-advanced-main',
                'admin.chunks._event-create-main',
                'admin.chunks._event-tickets-main',
                'admin.chunks._event-content-main',
            ],
            function ($view): void {
                $view->with($this->siteBrandingViewData());
            }
        );

        View::composer('admin.partials.admin-booking-notifications', function ($view): void {
            $user = auth()->user();
            if ($user === null || ! $user->canAccessAdminPanel() || $user->is_organizer) {
                $view->with([
                    'adminBookingNotificationCount' => 0,
                    'adminBookingNotificationItems' => collect(),
                ]);

                return;
            }

            $service = app(AdminBookingNotificationService::class);
            $view->with([
                'adminBookingNotificationCount' => $service->unreadCount($user),
                'adminBookingNotificationItems' => $service->recentUnread($user),
            ]);
        });

        View::composer(
            ['public.partials.classic-footer', 'public.layouts.frontend-default', 'public.layouts.classic', 'home.classic', 'home.default'],
            function ($view): void {
                $informationFooterPages = collect();
                try {
                    if (Schema::hasTable('pages')) {
                        $informationFooterPages = Page::query()
                            ->publishedOnFrontend()
                            ->orderBy('title')
                            ->get(['id', 'title', 'slug']);
                    }
                } catch (\Throwable) {
                }
                $view->with('informationFooterPages', $informationFooterPages);
                $view->with($this->publicSiteBranding());
            },
        );

        View::composer(
            ['public.partials.classic-header', 'public.layouts.classic'],
            fn($view) => $view->with($this->publicSiteBranding()),
        );
    }

    /**
     * @return array{siteName: string, siteLogoUrl: ?string, socialLinks: list<array{url: string, icon: string, label: string}>, footerCopyrightLine: string}
     */
    private function publicSiteBranding(): array
    {
        $defaultName = (string) config('app.name', 'Event Manager');
        $defaultCopyright = '© '.date('Y').' '.$defaultName.'. All rights reserved.';
        $empty = [
            'siteName' => $defaultName,
            'siteLogoUrl' => null,
            'siteFaviconUrl' => null,
            'socialLinks' => [],
            'footerCopyrightLine' => $defaultCopyright,
        ];

        try {
            if (!Schema::hasTable('site_settings')) {
                return $empty;
            }

            $s = SiteSetting::query()->first();
            if ($s === null) {
                return $empty;
            }

            $name = trim((string) ($s->site_name ?? '')) !== ''
                ? (string) $s->site_name
                : $defaultName;

            $socialLinks = [];
            if (Schema::hasColumn('site_settings', 'social_facebook_url')) {
                $socialLinks = $s->footerSocialLinks();
            }

            $footerCopyrightLine = $defaultCopyright;
            if (Schema::hasColumn('site_settings', 'footer_copyright_text')) {
                $footerCopyrightLine = $s->footerCopyrightLine($name);
            }

            $siteFaviconUrl = null;
            if (Schema::hasColumn('site_settings', 'favicon_path')) {
                $siteFaviconUrl = $s->faviconPublicUrl();
            }

            return [
                'siteName' => $name,
                'siteLogoUrl' => PublicFrontendTheme::resolvePublicLogoUrl($s),
                'siteFaviconUrl' => $siteFaviconUrl,
                'socialLinks' => $socialLinks,
                'footerCopyrightLine' => $footerCopyrightLine,
            ];
        } catch (\Throwable) {
            return $empty;
        }
    }

    /**
     * @return array{siteSetting: SiteSetting, siteLogoUrl: ?string, siteDisplayName: string}
     */
    private function siteBrandingViewData(): array
    {
        if (!Schema::hasTable('site_settings')) {
            $siteSetting = new SiteSetting([
                'site_name' => config('app.name', 'Event Manager'),
                'frontend_theme' => 'default',
            ]);
        } else {
            $siteSetting = SiteSetting::instance();
        }

        $siteFaviconUrl = null;
        if (Schema::hasColumn('site_settings', 'favicon_path')) {
            $siteFaviconUrl = $siteSetting->faviconPublicUrl();
        }

        return [
            'siteSetting' => $siteSetting,
            'siteLogoUrl' => $siteSetting->adminBrandLogoUrl(),
            'siteFaviconUrl' => $siteFaviconUrl,
            'siteDisplayName' => $siteSetting->site_name ?: 'Event Manager',
        ];
    }

    /**
     * Before installation completes, database-backed sessions/cache often fail (no tables yet).
     * Writing SESSION_DRIVER=file to .env in the installer does not affect config loaded at
     * bootstrap — StartSession runs before controllers — so we force file drivers here for /install*.
     */
    private function configureInstallerSessionsAndCache(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        if (InstallationStatus::completed()) {
            return;
        }

        /** @var Request $request */
        $request = $this->app->make(Request::class);

        $path = ltrim((string) $request->path(), '/');
        if ($path !== 'install' && !str_starts_with($path, 'install/')) {
            return;
        }

        Config::set([
            'session.driver' => 'file',
            'cache.default' => 'file',
        ]);
    }

    /**
     * Stripe, PayPal, Razorpay, etc. receive absolute return URLs from route(..., true).
     * On localhost, APP_URL often omits the port or uses "localhost" while the browser uses
     * 127.0.0.1:8000 — PayPal then redirects to a host that does not hit this app.
     */
    private function forcePublicUrlRootFromRequestWhenLocal(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        if (!$this->app->environment('local')) {
            return;
        }

        $this->app->booted(function (): void {
            if ($this->app->runningInConsole()) {
                return;
            }

            $request = request();
            if (!$request instanceof Request) {
                return;
            }

            $host = $request->getHost();
            if ($host === '' || $host === '0.0.0.0') {
                return;
            }

            $root = $request->getSchemeAndHttpHost();
            if ($root !== '') {
                URL::forceRootUrl($root);
            }
        });
    }

    private function configureMailFromDatabase(): void
    {
        try {
            if (!Schema::hasTable('site_settings')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $s = SiteSetting::query()->first();

        if (!$s || !$s->smtp_host) {
            return;
        }

        Config::set([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $s->smtp_encryption === 'ssl' ? 'smtps' : null,
            'mail.mailers.smtp.host' => $s->smtp_host,
            'mail.mailers.smtp.port' => $s->smtp_port ?: 587,
            'mail.mailers.smtp.username' => $s->smtp_username,
            'mail.mailers.smtp.password' => $s->smtp_password,
        ]);

        if ($s->smtp_from_address) {
            Config::set('mail.from.address', $s->smtp_from_address);
        }
        if ($s->smtp_from_name) {
            Config::set('mail.from.name', $s->smtp_from_name);
        }
    }
}
