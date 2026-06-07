<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Models\SiteHomeFaq;
use App\Models\SiteSetting;
use App\Support\PublicUploadStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const SETTINGS_SECTIONS = ['site', 'home', 'admin', 'email', 'payments', 'ai_reports'];

    public function index(Request $request): View
    {
        $section = (string) $request->query('section', 'site');
        if (! in_array($section, self::SETTINGS_SECTIONS, true)) {
            $section = 'site';
        }

        return view('admin.settings.index', [
            'activeNav' => 'settings',
            'settingsSection' => $section,
        ]);
    }

    public function update(UpdateSiteSettingsRequest $request): RedirectResponse
    {
        $settings = SiteSetting::query()->first();
        if ($settings === null) {
            $settings = SiteSetting::query()->create([
                'site_name' => config('app.name', 'Event Manager'),
                'frontend_theme' => 'default',
                'paypal_mode' => 'sandbox',
            ]);
        }

        $data = $request->validated();
        unset($data['logo'], $data['favicon'], $data['admin_logo'], $data['frontend_hero_image'], $data['clear_frontend_hero'], $data['clear_admin_logo'], $data['clear_favicon'], $data['home_faqs']);

        foreach (['smtp_password', 'stripe_secret_key', 'stripe_webhook_secret', 'paypal_secret', 'razorpay_key_secret', 'sslcommerz_store_password', 'report_ai_api_key'] as $secretKey) {
            if (! array_key_exists($secretKey, $data) || $data[$secretKey] === null || $data[$secretKey] === '') {
                unset($data[$secretKey]);
            }
        }

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            if (! $logo->isValid()) {
                return back()->withInput()->withErrors(['logo' => $logo->getErrorMessage()]);
            }
            if (filled($settings->logo_path)) {
                Storage::disk('uploads')->delete($settings->logo_path);
            }
            $storedLogo = PublicUploadStorage::store($logo, 'site');
            if ($storedLogo === null) {
                return back()->withInput()->withErrors([
                    'logo' => __('Could not save the logo. Check PHP upload limits (upload_max_filesize, post_max_size) and disk permissions.'),
                ]);
            }
            $data['logo_path'] = $storedLogo;
        }

        if ($request->hasFile('favicon')) {
            $favicon = $request->file('favicon');
            if (! $favicon->isValid()) {
                return back()->withInput()->withErrors(['favicon' => $favicon->getErrorMessage()]);
            }
            if (filled($settings->favicon_path)) {
                Storage::disk('uploads')->delete($settings->favicon_path);
            }
            $storedFavicon = PublicUploadStorage::store($favicon, 'site/favicons');
            if ($storedFavicon === null) {
                return back()->withInput()->withErrors([
                    'favicon' => __('Could not save the favicon. Check PHP upload limits and disk permissions.'),
                ]);
            }
            $data['favicon_path'] = $storedFavicon;
        } elseif ($request->boolean('clear_favicon') && filled($settings->favicon_path)) {
            Storage::disk('uploads')->delete($settings->favicon_path);
            $data['favicon_path'] = null;
        }

        if ($request->hasFile('admin_logo')) {
            $adminLogo = $request->file('admin_logo');
            if (! $adminLogo->isValid()) {
                return back()->withInput()->withErrors(['admin_logo' => $adminLogo->getErrorMessage()]);
            }
            if (filled($settings->admin_logo_path)) {
                Storage::disk('uploads')->delete($settings->admin_logo_path);
            }
            $storedAdminLogo = PublicUploadStorage::store($adminLogo, 'site/admin');
            if ($storedAdminLogo === null) {
                return back()->withInput()->withErrors([
                    'admin_logo' => __('Could not save the admin logo. Check PHP upload limits and disk permissions.'),
                ]);
            }
            $data['admin_logo_path'] = $storedAdminLogo;
        } elseif ($request->boolean('clear_admin_logo') && filled($settings->admin_logo_path)) {
            Storage::disk('uploads')->delete($settings->admin_logo_path);
            $data['admin_logo_path'] = null;
        }

        if ($request->hasFile('frontend_hero_image')) {
            $hero = $request->file('frontend_hero_image');
            if (! $hero->isValid()) {
                return back()->withInput()->withErrors(['frontend_hero_image' => $hero->getErrorMessage()]);
            }
            if (filled($settings->frontend_hero_image_path)) {
                Storage::disk('uploads')->delete($settings->frontend_hero_image_path);
            }
            $storedHero = PublicUploadStorage::store($hero, 'site/hero');
            if ($storedHero === null) {
                return back()->withInput()->withErrors([
                    'frontend_hero_image' => __('Could not save the hero image. Check PHP upload limits and disk permissions.'),
                ]);
            }
            $data['frontend_hero_image_path'] = $storedHero;
        } elseif ($request->boolean('clear_frontend_hero') && filled($settings->frontend_hero_image_path)) {
            Storage::disk('uploads')->delete($settings->frontend_hero_image_path);
            $data['frontend_hero_image_path'] = null;
        }

        if (! Schema::hasColumn('site_settings', 'social_media_order')) {
            unset($data['social_media_order']);
        }
        if (! Schema::hasColumn('site_settings', 'footer_copyright_text')) {
            unset($data['footer_copyright_text']);
        }
        if (! Schema::hasColumn('site_settings', 'favicon_path')) {
            unset($data['favicon_path']);
        }

        foreach ([
            'home_meta_title',
            'home_meta_description',
            'home_contact_eyebrow',
            'home_contact_title_before',
            'home_contact_title_highlight',
            'home_contact_lead',
        ] as $homeColumn) {
            if (! Schema::hasColumn('site_settings', $homeColumn)) {
                unset($data[$homeColumn]);
            }
        }

        DB::transaction(function () use ($settings, $data, $request): void {
            $settings->update($data);
            $this->syncHomeFaqs($request);
        });

        $after = (string) $request->input('settings_section', 'site');
        if (! in_array($after, self::SETTINGS_SECTIONS, true)) {
            $after = 'site';
        }

        return redirect()
            ->route('admin.settings.index', ['section' => $after])
            ->with('success', 'Settings saved.');
    }

    private function syncHomeFaqs(Request $request): void
    {
        try {
            if (! Schema::hasTable('site_home_faqs')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $rows = collect($request->input('home_faqs', []))
            ->map(function (mixed $row): array {
                if (! is_array($row)) {
                    return ['question' => '', 'answer' => ''];
                }

                return [
                    'question' => trim((string) ($row['question'] ?? '')),
                    'answer' => trim((string) ($row['answer'] ?? '')),
                ];
            })
            ->filter(fn (array $row): bool => $row['question'] !== '')
            ->values()
            ->map(fn (array $row, int $index): array => [
                'sort_order' => $index,
                'question' => $row['question'],
                'answer' => $row['answer'],
            ]);

        SiteHomeFaq::query()->delete();

        foreach ($rows as $row) {
            SiteHomeFaq::query()->create($row);
        }
    }
}
