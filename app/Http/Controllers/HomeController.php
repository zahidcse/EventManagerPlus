<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\SiteHomeFaq;
use App\Models\SiteSetting;
use App\Support\PublicFrontendTheme;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        if (! Schema::hasTable('site_settings')) {
            $setting = new SiteSetting([
                'site_name' => config('app.name', 'Event Manager'),
                'frontend_theme' => 'default',
            ]);
        } else {
            $setting = SiteSetting::instance();
        }

        $theme = PublicFrontendTheme::resolvedKey();

        $featuredEvents = collect();
        if (Schema::hasTable('events')) {
            $eventsQuery = Event::query()
                ->with(['eventCategory', 'tickets'])
                ->where('visibility', 'public')
                ->where('status', 'active')
                ->whereNotNull('starts_at')
                ->upcomingForPublicListing();

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

            $featuredEvents = $eventsQuery
                ->orderBy('starts_at')
                ->limit(6)
                ->get();
        }

        $view = PublicFrontendTheme::isClassicFamily() ? 'home.classic' : 'home.default';

        $homeFaqs = collect();
        if (PublicFrontendTheme::isClassicFamily()) {
            try {
                if (Schema::hasTable('site_home_faqs')) {
                    $homeFaqs = SiteHomeFaq::query()->orderBy('sort_order')->orderBy('id')->get();
                }
            } catch (\Throwable) {
            }
        }

        $socialLinks = Schema::hasColumn('site_settings', 'social_facebook_url')
            ? $setting->footerSocialLinks()
            : [];

        $siteName = $setting->site_name ?: config('app.name', 'Event Manager');
        $footerCopyrightLine = Schema::hasColumn('site_settings', 'footer_copyright_text')
            ? $setting->footerCopyrightLine($siteName)
            : '© '.date('Y').' '.$siteName.'. All rights reserved.';
        $siteFaviconUrl = Schema::hasColumn('site_settings', 'favicon_path')
            ? $setting->faviconPublicUrl()
            : null;

        return view($view, [
            'siteSetting' => $setting,
            'siteName' => $siteName,
            'siteLogoUrl' => PublicFrontendTheme::resolvePublicLogoUrl($setting),
            'siteFaviconUrl' => $siteFaviconUrl,
            'socialLinks' => $socialLinks,
            'footerCopyrightLine' => $footerCopyrightLine,
            'contactEmail' => $setting->contact_email,
            'contactPhone' => $setting->contact_phone,
            'featuredEvents' => $featuredEvents,
            'heroImageUrl' => PublicFrontendTheme::resolveHeroImageUrl($setting),
            'homeFaqs' => $homeFaqs,
        ]);
    }
}
