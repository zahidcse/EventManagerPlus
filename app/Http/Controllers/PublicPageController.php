<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\PublicFrontendTheme;
use App\Support\RichTextSanitizer;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PublicPageController extends Controller
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

    public function show(Page $page): View
    {
        abort_unless($page->isVisibleOnFrontend(), 404);

        $bodyHtml = RichTextSanitizer::html($page->body);

        return view(PublicFrontendTheme::pageView('show'), array_merge($this->siteContext(), [
            'page' => $page,
            'bodyHtml' => $bodyHtml,
        ]));
    }
}
