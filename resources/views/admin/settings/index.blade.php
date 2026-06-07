@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
@php
    $sec = old('settings_section', $settingsSection ?? 'site');
    if (! in_array($sec, ['site', 'home', 'admin', 'email', 'payments', 'ai_reports'], true)) {
        $sec = 'site';
    }
    $homeDefaults = \App\Models\SiteSetting::homeContentDefaults();
@endphp
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search settings...'])
<div class="mt-16 p-8 max-w-4xl pb-32 space-y-6">
<div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between">
<div>
<h2 class="text-2xl font-bold text-on-surface">Settings</h2>
<p class="text-on-surface-variant mt-1 max-w-xl">Manage the public site, admin panel, email delivery, payments, and the optional AI-powered reports assistant. Secrets stay encrypted at rest.</p>
</div>
<div class="w-full sm:w-72 shrink-0 space-y-1">
<label for="settings-section-picker" class="text-label-md font-semibold text-on-surface">Section</label>
<select id="settings-section-picker" aria-label="Choose settings section" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm bg-white outline-none focus:ring-2 focus:ring-primary/25">
<option value="site" @selected($sec === 'site')>Site settings</option>
<option value="home" @selected($sec === 'home')>Home settings</option>
<option value="admin" @selected($sec === 'admin')>Admin settings</option>
<option value="email" @selected($sec === 'email')>Email settings</option>
<option value="payments" @selected($sec === 'payments')>Payment settings</option>
<option value="ai_reports" @selected($sec === 'ai_reports')>AI reports</option>
</select>
</div>
</div>
@if(session('success'))
<div class="rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-on-surface">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="rounded-xl border border-error/40 bg-error-container/30 px-4 py-3 text-body-sm text-error">
<ul class="list-disc pl-5 space-y-1">
@foreach($errors->all() as $err)
<li>{{ $err }}</li>
@endforeach
</ul>
</div>
@endif
<form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
@csrf
@method('PUT')
<input type="hidden" name="settings_section" id="settings_section_field" value="{{ old('settings_section', $sec) }}"/>
<div class="settings-panel space-y-6 {{ $sec === 'site' ? '' : 'hidden' }}" data-panel="site">
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">Public site theme</h3>
<p class="text-body-sm text-on-surface-variant">Choose how the homepage at <span class="font-mono text-xs">{{ url('/') }}</span> is rendered for visitors.</p>
@php
    $ft = old('frontend_theme', $siteSetting->frontend_theme ?? 'default');
    if (($editionIsFree ?? false) && ! \App\Support\Edition::allowsTheme($ft)) {
        $ft = 'default';
    }
    $themeOptions = \App\Support\PublicFrontendTheme::adminThemeOptions();
@endphp
<div id="frontend-theme-picker" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
@foreach($themeOptions as $themeKey => $themeOpt)
@php $themeIsPremium = ($editionIsFree ?? false) && ! \App\Support\Edition::allowsTheme($themeKey); @endphp
<label class="frontend-theme-card group relative flex flex-col rounded-xl border-2 border-outline-variant transition-colors overflow-hidden {{ $themeIsPremium ? 'opacity-80 cursor-not-allowed' : 'cursor-pointer hover:border-primary/40' }}">
@if($themeIsPremium)
<div class="absolute top-2 left-2 z-10">@include('admin.chunks._premium-badge')</div>
@endif
<input type="radio" name="frontend_theme" value="{{ $themeKey }}" class="sr-only" @checked($ft === $themeKey && ! $themeIsPremium) @disabled($themeIsPremium)/>
@if(!empty($themeOpt['preview']))
<img src="{{ asset($themeOpt['preview']) }}" alt="" class="w-full aspect-[16/10] object-cover object-top bg-surface-container-low"/>
@else
<div class="w-full aspect-[16/10] flex items-center justify-center bg-surface-container-low text-on-surface-variant">
<span class="material-symbols-outlined text-[40px] opacity-40">web</span>
</div>
@endif
<div class="p-3">
<span class="text-body-md font-semibold text-on-surface block">{{ $themeOpt['label'] }}</span>
@if(filled($themeOpt['description'] ?? ''))
<span class="text-body-sm text-on-surface-variant block mt-0.5">{{ $themeOpt['description'] }}</span>
@endif
</div>
<span class="frontend-theme-check absolute top-2 right-2 hidden items-center justify-center w-7 h-7 rounded-full bg-primary text-white shadow-sm" aria-hidden="true">
<span class="material-symbols-outlined text-[18px]">check</span>
</span>
</label>
@endforeach
</div>
<p class="text-body-sm text-on-surface-variant pt-2">Homepage SEO, hero, how-it-works, FAQs, and contact copy are configured under <strong>Home settings</strong>.</p>
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">Branding</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Public site logo</label>
<p class="text-body-sm text-on-surface-variant mb-2">Navbar and footer on the public site (Classic / Default themes).</p>
@if($siteSetting->logo_path)
<p class="text-body-sm text-on-surface-variant mb-2">Current: <img src="{{ $siteSetting->logoPublicUrl() }}" alt="" class="inline-block h-10 w-auto rounded border border-outline-variant ml-2 align-middle"/></p>
@endif
<input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-semibold"/>
<p class="text-[11px] text-on-surface-variant mt-1">PNG, JPG, or WebP — max 4&nbsp;MB.</p>
@error('logo')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Site favicon</label>
<p class="text-body-sm text-on-surface-variant mb-2">Browser tab icon for the public site and admin panel. Square images work best.</p>
@if($siteSetting->favicon_path)
<p class="text-body-sm text-on-surface-variant mb-2 flex items-center gap-2">Current: <img src="{{ $siteSetting->faviconPublicUrl() }}" alt="" class="inline-block h-8 w-8 rounded border border-outline-variant object-contain bg-white p-0.5"/></p>
<label class="inline-flex items-center gap-2 text-body-sm text-on-surface-variant cursor-pointer mb-2">
<input type="checkbox" name="clear_favicon" value="1" class="rounded border-outline-variant text-primary" @checked(old('clear_favicon'))/>
Remove current favicon
</label>
@endif
<input type="file" name="favicon" accept="image/png,image/jpeg,image/webp,image/x-icon,image/vnd.microsoft.icon,image/svg+xml,.ico" class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-semibold"/>
<p class="text-[11px] text-on-surface-variant mt-1">PNG, ICO, SVG, or WebP — max 1&nbsp;MB. If unset, the public logo is used when available.</p>
@error('favicon')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
</div>
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-4 relative">
@if($editionIsFree ?? false)
<div class="absolute top-4 right-4 z-10">@include('admin.chunks._premium-badge')</div>
@endif
<h3 class="font-semibold text-headline-md text-on-surface">Seat plan</h3>
<p class="text-body-sm text-on-surface-variant">Turn on visual seat layout tools for events. When enabled, admins can create and edit seat plans from the Events menu.</p>
<label class="inline-flex items-start gap-3 {{ ($editionIsFree ?? false) ? 'opacity-60 pointer-events-none select-none' : 'cursor-pointer' }}">
<input type="hidden" name="seat_plan_enabled" value="0"/>
<input type="checkbox" name="seat_plan_enabled" value="1" class="rounded border-outline-variant text-primary mt-0.5 shrink-0" @checked(old('seat_plan_enabled', ($siteSetting->seat_plan_enabled ?? false) ? '1' : '0') === '1') @disabled($editionIsFree ?? false)/>
<span class="text-body-sm text-on-surface"><span class="font-semibold">Enable seat plan</span></span>
</label>
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">General &amp; contact</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Site name</label>
<input type="text" name="site_name" value="{{ old('site_name', $siteSetting->site_name) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Contact email</label>
<input type="email" name="contact_email" value="{{ old('contact_email', $siteSetting->contact_email) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Contact phone</label>
<input type="text" name="contact_phone" value="{{ old('contact_phone', $siteSetting->contact_phone) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Footer copyright</label>
<input type="text" name="footer_copyright_text" value="{{ old('footer_copyright_text', $siteSetting->footer_copyright_text) }}" placeholder="© {year} {site_name}. All rights reserved." class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
<p class="text-[11px] text-on-surface-variant mt-1">Shown in the public site footer. Use <span class="font-mono text-[10px]">{year}</span> and <span class="font-mono text-[10px]">{site_name}</span> placeholders, or leave blank for the default line.</p>
@error('footer_copyright_text')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
</div>
</div>
@php
  $socialPlatformDefinitions = \App\Models\SiteSetting::socialPlatformDefinitions();
  $socialOrder = old('social_media_order');
  if (! is_array($socialOrder)) {
    $socialOrder = $siteSetting->socialMediaOrderResolved();
  }
  $socialOrder = array_values(array_filter($socialOrder, fn ($key) => is_string($key) && isset($socialPlatformDefinitions[$key])));
  $socialPlaceholders = [
    'facebook' => 'https://facebook.com/...',
    'twitter' => 'https://x.com/...',
    'instagram' => 'https://instagram.com/...',
    'youtube' => 'https://youtube.com/...',
    'linkedin' => 'https://linkedin.com/company/...',
  ];
@endphp
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-4">
<h3 class="font-semibold text-headline-md text-on-surface">Social media</h3>
<p class="text-body-sm text-on-surface-variant">Profile links shown in the public site footer. Drag rows to set icon order. Leave a URL blank to hide that network.</p>
@error('social_media_order')<p class="text-error text-xs">{{ $message }}</p>@enderror
@error('social_media_order.*')<p class="text-error text-xs">{{ $message }}</p>@enderror
<ul id="social-media-sortable" class="space-y-3 list-none m-0 p-0">
@foreach($socialOrder as $socialKey)
  @php $socialDef = $socialPlatformDefinitions[$socialKey]; @endphp
  <li class="social-sort-row flex items-start gap-3 rounded-xl border border-outline-variant bg-surface-container-low/30 p-4 transition-shadow" data-social-key="{{ $socialKey }}">
    <button type="button" class="social-drag-handle inline-flex shrink-0 items-center justify-center w-9 h-9 rounded-lg border border-outline-variant bg-white text-on-surface-variant cursor-grab active:cursor-grabbing touch-none mt-6" draggable="true" aria-label="Drag to reorder {{ $socialDef['label'] }}" title="Drag to reorder">
      <span class="material-symbols-outlined text-[20px]">drag_indicator</span>
    </button>
    <div class="min-w-0 flex-1 space-y-1">
      <label class="text-label-md font-semibold text-on-surface">{{ $socialDef['label'] }}</label>
      <input type="url" name="{{ $socialDef['field'] }}" value="{{ old($socialDef['field'], $siteSetting->{$socialDef['field']}) }}" placeholder="{{ $socialPlaceholders[$socialKey] ?? 'https://' }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm bg-white outline-none focus:ring-2 focus:ring-primary/25"/>
      @error($socialDef['field'])<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <input type="hidden" name="social_media_order[]" value="{{ $socialKey }}" class="social-order-input"/>
  </li>
@endforeach
</ul>
</div>
</div>
<div class="settings-panel space-y-6 {{ $sec === 'home' ? '' : 'hidden' }}" data-panel="home">
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">SEO &amp; meta tags</h3>
<p class="text-body-sm text-on-surface-variant">Browser title and description for the public homepage (Classic and Default themes). Leave blank to use built-in defaults.</p>
<div class="grid grid-cols-1 gap-4">
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Meta title</label>
<input type="text" name="home_meta_title" value="{{ old('home_meta_title', $siteSetting->home_meta_title) }}" placeholder="{{ $siteDisplayName ?? 'Event Manager' }} — Book tickets to unforgettable events" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25" maxlength="255"/>
@error('home_meta_title')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Meta description</label>
<textarea name="home_meta_description" rows="3" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25" placeholder="Discover and book tickets to concerts, festivals, conferences and more.">{{ old('home_meta_description', $siteSetting->home_meta_description) }}</textarea>
<p class="text-[11px] text-on-surface-variant mt-1">Aim for about 150–160 characters for search snippets.</p>
@error('home_meta_description')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
</div>
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">Hero section</h3>
<p class="text-body-sm text-on-surface-variant">Classic theme homepage hero (background image and main copy). Leave fields blank to use built-in defaults.</p>
<div class="space-y-3 pt-2 border-t border-outline-variant">
<label class="text-label-md font-semibold text-on-surface">Background image</label>
<p class="text-body-sm text-on-surface-variant">Large photo behind the hero. If unset, a default stock image is used.</p>
@if(!empty($siteSetting->frontend_hero_image_path))
<p class="text-body-sm text-on-surface-variant">Current preview:</p>
<img src="{{ $siteSetting->classicHeroPublicUrl() }}" alt="" class="w-full max-w-xl h-40 object-cover rounded-lg border border-outline-variant"/>
@endif
<input type="file" name="frontend_hero_image" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-semibold"/>
<p class="text-[11px] text-on-surface-variant">PNG, JPG, or WebP — max 8&nbsp;MB. Wide landscape photos work best.</p>
@if(!empty($siteSetting->frontend_hero_image_path))
<label class="inline-flex items-center gap-2 cursor-pointer mt-2">
<input type="checkbox" name="clear_frontend_hero" value="1" class="rounded border-outline-variant text-primary" @checked(old('clear_frontend_hero'))/>
<span class="text-body-sm text-on-surface">Remove custom hero (use default image)</span>
</label>
@endif
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Badge text</label>
<input type="text" name="home_hero_badge" value="{{ old('home_hero_badge', $siteSetting->home_hero_badge) }}" placeholder="{{ $homeDefaults['home_hero_badge'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Headline (before highlight)</label>
<input type="text" name="home_hero_headline_before" value="{{ old('home_hero_headline_before', $siteSetting->home_hero_headline_before) }}" placeholder="{{ $homeDefaults['home_hero_headline_before'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Headline highlight</label>
<input type="text" name="home_hero_headline_highlight" value="{{ old('home_hero_headline_highlight', $siteSetting->home_hero_headline_highlight) }}" placeholder="{{ $homeDefaults['home_hero_headline_highlight'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
<p class="text-[11px] text-on-surface-variant">Shown with gradient styling in the headline.</p>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Headline (after highlight)</label>
<input type="text" name="home_hero_headline_suffix" value="{{ old('home_hero_headline_suffix', $siteSetting->home_hero_headline_suffix) }}" placeholder="{{ $homeDefaults['home_hero_headline_suffix'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Lead paragraph</label>
<textarea name="home_hero_lead" rows="2" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25" placeholder="{{ $homeDefaults['home_hero_lead'] }}">{{ old('home_hero_lead', $siteSetting->home_hero_lead) }}</textarea>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Primary button label</label>
<input type="text" name="home_hero_cta_primary_label" value="{{ old('home_hero_cta_primary_label', $siteSetting->home_hero_cta_primary_label) }}" placeholder="{{ $homeDefaults['home_hero_cta_primary_label'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Secondary button label</label>
<input type="text" name="home_hero_cta_secondary_label" value="{{ old('home_hero_cta_secondary_label', $siteSetting->home_hero_cta_secondary_label) }}" placeholder="{{ $homeDefaults['home_hero_cta_secondary_label'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
</div>
<div class="pt-4 border-t border-outline-variant space-y-4">
<p class="text-label-md font-semibold text-on-surface">Hero stats</p>
<p class="text-body-sm text-on-surface-variant">The first stat value is filled automatically from upcoming events. You can customize its label and the second and third stat blocks.</p>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<div class="space-y-1">
<label class="text-label-md text-on-surface-variant">Stat 1 label</label>
<input type="text" name="home_hero_stat_1_label" value="{{ old('home_hero_stat_1_label', $siteSetting->home_hero_stat_1_label) }}" placeholder="{{ $homeDefaults['home_hero_stat_1_label'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md text-on-surface-variant">Stat 2 value</label>
<input type="text" name="home_hero_stat_2_value" value="{{ old('home_hero_stat_2_value', $siteSetting->home_hero_stat_2_value) }}" placeholder="{{ $homeDefaults['home_hero_stat_2_value'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md text-on-surface-variant">Stat 2 label</label>
<input type="text" name="home_hero_stat_2_label" value="{{ old('home_hero_stat_2_label', $siteSetting->home_hero_stat_2_label) }}" placeholder="{{ $homeDefaults['home_hero_stat_2_label'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md text-on-surface-variant">Stat 3 value</label>
<input type="text" name="home_hero_stat_3_value" value="{{ old('home_hero_stat_3_value', $siteSetting->home_hero_stat_3_value) }}" placeholder="{{ $homeDefaults['home_hero_stat_3_value'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md text-on-surface-variant">Stat 3 label</label>
<input type="text" name="home_hero_stat_3_label" value="{{ old('home_hero_stat_3_label', $siteSetting->home_hero_stat_3_label) }}" placeholder="{{ $homeDefaults['home_hero_stat_3_label'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
</div>
</div>
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">How it works</h3>
<p class="text-body-sm text-on-surface-variant">Section heading and three steps on the Classic homepage.</p>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Eyebrow</label>
<input type="text" name="home_how_eyebrow" value="{{ old('home_how_eyebrow', $siteSetting->home_how_eyebrow) }}" placeholder="{{ $homeDefaults['home_how_eyebrow'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Title (before highlight)</label>
<input type="text" name="home_how_title_before" value="{{ old('home_how_title_before', $siteSetting->home_how_title_before) }}" placeholder="{{ $homeDefaults['home_how_title_before'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Title highlight</label>
<input type="text" name="home_how_title_highlight" value="{{ old('home_how_title_highlight', $siteSetting->home_how_title_highlight) }}" placeholder="{{ $homeDefaults['home_how_title_highlight'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
</div>
@foreach([1, 2, 3] as $step)
<div class="rounded-lg border border-outline-variant p-4 space-y-3 bg-surface-container-lowest/50">
<p class="text-label-md font-semibold text-on-surface">Step {{ $step }}</p>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="space-y-1">
<label class="text-label-md text-on-surface-variant">Title</label>
<input type="text" name="home_how_step{{ $step }}_title" value="{{ old('home_how_step'.$step.'_title', $siteSetting->{'home_how_step'.$step.'_title'}) }}" placeholder="{{ $homeDefaults['home_how_step'.$step.'_title'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md text-on-surface-variant">Description</label>
<textarea name="home_how_step{{ $step }}_description" rows="2" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25" placeholder="{{ $homeDefaults['home_how_step'.$step.'_description'] }}">{{ old('home_how_step'.$step.'_description', $siteSetting->{'home_how_step'.$step.'_description'}) }}</textarea>
</div>
</div>
</div>
@endforeach
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">FAQ section</h3>
<p class="text-body-sm text-on-surface-variant">Section title and question/answer pairs for the Classic homepage. Order matches the form top to bottom.</p>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 pb-4 border-b border-outline-variant">
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Section eyebrow</label>
<input type="text" name="home_faq_eyebrow" value="{{ old('home_faq_eyebrow', $siteSetting->home_faq_eyebrow) }}" placeholder="{{ $homeDefaults['home_faq_eyebrow'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Section title (before highlight)</label>
<input type="text" name="home_faq_title_before" value="{{ old('home_faq_title_before', $siteSetting->home_faq_title_before) }}" placeholder="{{ $homeDefaults['home_faq_title_before'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Section title highlight</label>
<input type="text" name="home_faq_title_highlight" value="{{ old('home_faq_title_highlight', $siteSetting->home_faq_title_highlight) }}" placeholder="{{ $homeDefaults['home_faq_title_highlight'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
</div>
@php
    $faqRows = old('home_faqs');
    if (! is_array($faqRows)) {
        $faqRows = isset($homeFaqs) && $homeFaqs->isNotEmpty()
            ? $homeFaqs->map(fn ($f) => ['question' => $f->question, 'answer' => $f->answer])->all()
            : [['question' => '', 'answer' => '']];
    }
    if (count($faqRows) === 0) {
        $faqRows = [['question' => '', 'answer' => '']];
    }
@endphp
<div id="home-faq-rows" class="space-y-4">
@foreach($faqRows as $idx => $row)
<div class="home-faq-row rounded-lg border border-outline-variant p-4 space-y-3 bg-surface-container-lowest/50">
<div class="flex justify-between items-center gap-2">
<span class="text-label-md font-semibold text-on-surface home-faq-index">FAQ {{ $idx + 1 }}</span>
@if($idx > 0)
<button type="button" class="text-error text-body-sm font-semibold hover:underline home-faq-remove" aria-label="Remove this FAQ">Remove</button>
@endif
</div>
<div class="space-y-1">
<label class="text-label-md text-on-surface-variant">Question</label>
<input type="text" name="home_faqs[{{ $idx }}][question]" value="{{ $row['question'] ?? '' }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25" maxlength="500"/>
</div>
<div class="space-y-1">
<label class="text-label-md text-on-surface-variant">Answer</label>
<textarea name="home_faqs[{{ $idx }}][answer]" rows="3" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25">{{ $row['answer'] ?? '' }}</textarea>
</div>
</div>
@endforeach
</div>
<button type="button" id="home-faq-add" class="px-4 py-2 rounded-lg border border-outline-variant text-body-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">Add FAQ</button>
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">Contact us section</h3>
<p class="text-body-sm text-on-surface-variant">Heading and intro copy for the Classic homepage contact block. Email and phone come from <strong>Site settings → General &amp; contact</strong>.</p>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Section eyebrow</label>
<input type="text" name="home_contact_eyebrow" value="{{ old('home_contact_eyebrow', $siteSetting->home_contact_eyebrow) }}" placeholder="{{ $homeDefaults['home_contact_eyebrow'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Title (before highlight)</label>
<input type="text" name="home_contact_title_before" value="{{ old('home_contact_title_before', $siteSetting->home_contact_title_before) }}" placeholder="{{ $homeDefaults['home_contact_title_before'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Title highlight</label>
<input type="text" name="home_contact_title_highlight" value="{{ old('home_contact_title_highlight', $siteSetting->home_contact_title_highlight) }}" placeholder="{{ $homeDefaults['home_contact_title_highlight'] }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Intro paragraph</label>
<textarea name="home_contact_lead" rows="2" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25" placeholder="{{ $homeDefaults['home_contact_lead'] }}">{{ old('home_contact_lead', $siteSetting->home_contact_lead) }}</textarea>
</div>
</div>
</div>
</div>
@push('scripts')
<script>
(function () {
  const wrap = document.getElementById('home-faq-rows');
  const addBtn = document.getElementById('home-faq-add');
  if (!wrap || !addBtn) return;
  function bindRemove(row) {
    row.querySelectorAll('.home-faq-remove').forEach(function (btn) {
      btn.addEventListener('click', function () { row.remove(); renumber(); });
    });
  }
  function renumber() {
    wrap.querySelectorAll('.home-faq-row').forEach(function (row, i) {
      var idxEl = row.querySelector('.home-faq-index');
      if (idxEl) idxEl.textContent = 'FAQ ' + (i + 1);
      row.querySelectorAll('input[name^="home_faqs["], textarea[name^="home_faqs["]').forEach(function (el) {
        var n = el.getAttribute('name');
        if (n) el.setAttribute('name', n.replace(/^home_faqs\[\d+]/, 'home_faqs[' + i + ']'));
      });
      var hdr = row.querySelector('.flex.justify-between');
      var rm = row.querySelector('.home-faq-remove');
      if (i === 0 && rm) { rm.remove(); }
      else if (i > 0 && !rm && hdr) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'text-error text-body-sm font-semibold hover:underline home-faq-remove';
        b.setAttribute('aria-label', 'Remove this FAQ');
        b.textContent = 'Remove';
        b.addEventListener('click', function () { row.remove(); renumber(); });
        hdr.appendChild(b);
      }
    });
  }
  wrap.querySelectorAll('.home-faq-row').forEach(bindRemove);
  addBtn.addEventListener('click', function () {
    const n = wrap.querySelectorAll('.home-faq-row').length;
    const div = document.createElement('div');
    div.className = 'home-faq-row rounded-lg border border-outline-variant p-4 space-y-3 bg-surface-container-lowest/50';
    div.innerHTML =
      '<div class="flex justify-between items-center gap-2">' +
      '<span class="text-label-md font-semibold text-on-surface home-faq-index">FAQ ' + (n + 1) + '</span>' +
      '<button type="button" class="text-error text-body-sm font-semibold hover:underline home-faq-remove" aria-label="Remove this FAQ">Remove</button>' +
      '</div>' +
      '<div class="space-y-1"><label class="text-label-md text-on-surface-variant">Question</label>' +
      '<input type="text" name="home_faqs[' + n + '][question]" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25" maxlength="500"/></div>' +
      '<div class="space-y-1"><label class="text-label-md text-on-surface-variant">Answer</label>' +
      '<textarea name="home_faqs[' + n + '][answer]" rows="3" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"></textarea></div>';
    wrap.appendChild(div);
    bindRemove(div);
    renumber();
  });
})();
</script>
@endpush
<div class="settings-panel space-y-6 {{ $sec === 'admin' ? '' : 'hidden' }}" data-panel="admin">
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<div class="space-y-3 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Admin panel logo</label>
<p class="text-body-sm text-on-surface-variant">Sidebar and admin sign-in screen. Leave empty to reuse the public site logo.</p>
@if(filled($siteSetting->admin_logo_path))
<p class="text-body-sm text-on-surface-variant mb-2">Current: <img src="{{ $siteSetting->adminLogoPublicUrl() }}" alt="" class="inline-block h-10 w-auto rounded border border-outline-variant ml-2 align-middle"/></p>
<label class="inline-flex items-center gap-2 cursor-pointer mb-3">
<input type="checkbox" name="clear_admin_logo" value="1" class="rounded border-outline-variant text-primary" @checked(old('clear_admin_logo'))/>
<span class="text-body-sm text-on-surface">Remove admin logo (fallback to public site logo)</span>
</label>
@endif
<input type="file" name="admin_logo" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-semibold"/>
<p class="text-[11px] text-on-surface-variant mt-1">PNG, JPG, or WebP — max 4&nbsp;MB.</p>
</div>
</div>
</div>
<div class="settings-panel space-y-6 {{ $sec === 'email' ? '' : 'hidden' }}" data-panel="email">
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">SMTP (outbound email)</h3>
<p class="text-body-sm text-on-surface-variant">When host is set, these values override .env mail settings for the running app.</p>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Host</label>
<input type="text" name="smtp_host" value="{{ old('smtp_host', $siteSetting->smtp_host) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25" placeholder="smtp.mailtrap.io"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Port</label>
<input type="number" name="smtp_port" value="{{ old('smtp_port', $siteSetting->smtp_port) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25" placeholder="587"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Encryption</label>
<select name="smtp_encryption" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm bg-white outline-none focus:ring-2 focus:ring-primary/25">
@php $enc = old('smtp_encryption', $siteSetting->smtp_encryption); @endphp
<option value="" @selected($enc === null || $enc === '')>None</option>
<option value="tls" @selected($enc === 'tls')>TLS</option>
<option value="ssl" @selected($enc === 'ssl')>SSL / SMTPS</option>
</select>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Username</label>
<input type="text" name="smtp_username" value="{{ old('smtp_username', $siteSetting->smtp_username) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Password</label>
<input type="password" name="smtp_password" autocomplete="new-password" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25" placeholder="Leave blank to keep current"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">From address</label>
<input type="email" name="smtp_from_address" value="{{ old('smtp_from_address', $siteSetting->smtp_from_address) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">From name</label>
<input type="text" name="smtp_from_name" value="{{ old('smtp_from_name', $siteSetting->smtp_from_name) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25"/>
</div>
</div>
</div>
</div>
<div class="settings-panel space-y-6 {{ $sec === 'payments' ? '' : 'hidden' }}" data-panel="payments">
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">Stripe</h3>
<div class="flex items-center gap-2 mb-4">
<input type="hidden" name="stripe_enabled" value="0"/>
<input type="checkbox" name="stripe_enabled" value="1" class="rounded border-outline-variant text-primary" @checked(old('stripe_enabled', $siteSetting->stripe_enabled ? '1' : '0') === '1')/>
<label class="text-body-sm text-on-surface">Enable Stripe</label>
</div>
<div class="grid grid-cols-1 gap-4">
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Publishable key</label>
<input type="text" name="stripe_public_key" value="{{ old('stripe_public_key', $siteSetting->stripe_public_key) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm font-mono outline-none focus:ring-2 focus:ring-primary/25" autocomplete="off"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Secret key</label>
<input type="password" name="stripe_secret_key" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm font-mono outline-none focus:ring-2 focus:ring-primary/25" placeholder="Leave blank to keep current" autocomplete="new-password"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Webhook signing secret</label>
<input type="password" name="stripe_webhook_secret" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm font-mono outline-none focus:ring-2 focus:ring-primary/25" placeholder="Leave blank to keep current" autocomplete="new-password"/>
</div>
</div>
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">PayPal</h3>
<div class="{{ ($editionIsFree ?? false) ? 'relative' : '' }}">
@if($editionIsFree ?? false)
<div class="absolute top-0 right-0 z-10">@include('admin.chunks._premium-badge')</div>
@endif
<div class="{{ ($editionIsFree ?? false) ? 'opacity-60 pointer-events-none select-none' : '' }}">
<div class="flex items-center gap-2 mb-4">
<input type="hidden" name="paypal_enabled" value="0"/>
<input type="checkbox" name="paypal_enabled" value="1" class="rounded border-outline-variant text-primary" @checked(old('paypal_enabled', $siteSetting->paypal_enabled ? '1' : '0') === '1') @disabled($editionIsFree ?? false)/>
<label class="text-body-sm text-on-surface">Enable PayPal</label>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Client ID</label>
<input type="text" name="paypal_client_id" value="{{ old('paypal_client_id', $siteSetting->paypal_client_id) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm font-mono outline-none focus:ring-2 focus:ring-primary/25" autocomplete="off"/>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Secret</label>
<input type="password" name="paypal_secret" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm font-mono outline-none focus:ring-2 focus:ring-primary/25" placeholder="Leave blank to keep current" autocomplete="new-password"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Environment</label>
@php $pm = old('paypal_mode', $siteSetting->paypal_mode); @endphp
<select name="paypal_mode" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm bg-white outline-none focus:ring-2 focus:ring-primary/25">
<option value="sandbox" @selected($pm === 'sandbox')>Sandbox</option>
<option value="live" @selected($pm === 'live')>Live</option>
</select>
</div>
</div>
</div>
</div>
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">Manual / offline payments</h3>
<p class="text-body-sm text-on-surface-variant">When enabled, visitors with a paid cart can complete booking without Stripe or PayPal. They choose cash or bank transfer and enter a reference (transaction ID, receipt number, etc.). Orders are stored as pending until you confirm payment.</p>
<div class="{{ ($editionIsFree ?? false) ? 'relative' : '' }}">
@if($editionIsFree ?? false)
<div class="absolute top-0 right-0 z-10">@include('admin.chunks._premium-badge')</div>
@endif
<div class="space-y-4 {{ ($editionIsFree ?? false) ? 'opacity-60 pointer-events-none select-none' : '' }}">
<div class="flex items-center gap-2">
<input type="hidden" name="payment_cash_enabled" value="0"/>
<input type="checkbox" name="payment_cash_enabled" value="1" class="rounded border-outline-variant text-primary" @checked(old('payment_cash_enabled', $siteSetting->payment_cash_enabled ?? false)) @disabled($editionIsFree ?? false)/>
<label class="text-body-sm text-on-surface">Allow cash payment (pay on site / in person)</label>
</div>
<div class="flex items-center gap-2">
<input type="hidden" name="payment_bank_transfer_enabled" value="0"/>
<input type="checkbox" name="payment_bank_transfer_enabled" value="1" class="rounded border-outline-variant text-primary" @checked(old('payment_bank_transfer_enabled', $siteSetting->payment_bank_transfer_enabled ?? false)) @disabled($editionIsFree ?? false)/>
<label class="text-body-sm text-on-surface">Allow bank transfer</label>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Bank transfer instructions</label>
<p class="text-[11px] text-on-surface-variant">Shown on the booking form when bank transfer is selected (account name, IBAN, branch, etc.).</p>
<textarea name="bank_transfer_instructions" rows="5" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25 font-mono">{{ old('bank_transfer_instructions', $siteSetting->bank_transfer_instructions) }}</textarea>
</div>
</div>
</div>
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">Razorpay</h3>
<div class="{{ ($editionIsFree ?? false) ? 'relative' : '' }}">
@if($editionIsFree ?? false)
<div class="absolute top-0 right-0 z-10">@include('admin.chunks._premium-badge')</div>
@endif
<div class="{{ ($editionIsFree ?? false) ? 'opacity-60 pointer-events-none select-none' : '' }}">
<div class="flex items-center gap-2 mb-4">
<input type="hidden" name="razorpay_enabled" value="0"/>
<input type="checkbox" name="razorpay_enabled" value="1" class="rounded border-outline-variant text-primary" @checked(old('razorpay_enabled', $siteSetting->razorpay_enabled ? '1' : '0') === '1') @disabled($editionIsFree ?? false)/>
<label class="text-body-sm text-on-surface">Enable Razorpay</label>
</div>
<div class="grid grid-cols-1 gap-4">
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Key ID</label>
<input type="text" name="razorpay_key_id" value="{{ old('razorpay_key_id', $siteSetting->razorpay_key_id) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm font-mono outline-none focus:ring-2 focus:ring-primary/25" autocomplete="off"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Key secret</label>
<input type="password" name="razorpay_key_secret" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm font-mono outline-none focus:ring-2 focus:ring-primary/25" placeholder="Leave blank to keep current" autocomplete="new-password"/>
</div>
</div>
</div>
</div>
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">SSLCommerz (Bangladesh)</h3>
<p class="text-body-sm text-on-surface-variant">Hosted checkout for Bangladesh: cards, mobile banking (bKash, Nagad, Rocket, etc.), and internet banking via SSLCommerz. Enable after you configure your sandbox or live Store ID from the merchant panel.</p>
<div class="{{ ($editionIsFree ?? false) ? 'relative' : '' }}">
@if($editionIsFree ?? false)
<div class="absolute top-0 right-0 z-10">@include('admin.chunks._premium-badge')</div>
@endif
<div class="{{ ($editionIsFree ?? false) ? 'opacity-60 pointer-events-none select-none' : '' }}">
<div class="flex items-center gap-2 mb-4">
<input type="hidden" name="sslcommerz_enabled" value="0"/>
<input type="checkbox" name="sslcommerz_enabled" value="1" class="rounded border-outline-variant text-primary" @checked(old('sslcommerz_enabled', $siteSetting->sslcommerz_enabled ? '1' : '0') === '1') @disabled($editionIsFree ?? false)/>
<label class="text-body-sm text-on-surface">Enable SSLCommerz</label>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Store ID</label>
<input type="text" name="sslcommerz_store_id" value="{{ old('sslcommerz_store_id', $siteSetting->sslcommerz_store_id) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm font-mono outline-none focus:ring-2 focus:ring-primary/25" autocomplete="off"/>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">Store password (API)</label>
<input type="password" name="sslcommerz_store_password" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm font-mono outline-none focus:ring-2 focus:ring-primary/25" placeholder="Leave blank to keep current" autocomplete="new-password"/>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Environment</label>
@php $scm = old('sslcommerz_mode', $siteSetting->sslcommerz_mode ?? 'sandbox'); @endphp
<select name="sslcommerz_mode" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm bg-white outline-none focus:ring-2 focus:ring-primary/25">
<option value="sandbox" @selected($scm === 'sandbox')>Sandbox</option>
<option value="live" @selected($scm === 'live')>Live</option>
</select>
</div>
</div>
</div>
</div>
</div>
</div>
<div class="settings-panel space-y-6 {{ $sec === 'ai_reports' ? '' : 'hidden' }}" data-panel="ai_reports">
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm space-y-6">
<h3 class="font-semibold text-headline-md text-on-surface">AI reporting assistant</h3>
<p class="text-body-sm text-on-surface-variant">
Choose provider and model used on the Reports page for natural-language questions. Calls go from your server to the vendor APIs you configure here (billing and limits apply on their side). Keys use the same encryption as other gateway secrets — leave blank to keep the saved key unless you intentionally replace or clear via database.
</p>
<div class="flex items-start gap-2 pb-4 border-b border-outline-variant">
<input type="hidden" name="report_ai_enabled" value="0"/>
<input type="checkbox" name="report_ai_enabled" value="1" class="rounded border-outline-variant text-primary mt-0.5 shrink-0" @checked(old('report_ai_enabled', $siteSetting->report_ai_enabled ? '1' : '0') === '1')/>
<label class="space-y-0.5">
<span class="text-body-md font-semibold text-on-surface block">Enable AI-assisted reports</span>
<span class="text-body-sm text-on-surface-variant">When disabled, admins only see filters and CSV on the Reports page.</span>
</label>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Provider</label>
@php $raiProv = old('report_ai_provider', $siteSetting->report_ai_provider ?? 'openai'); @endphp
<select name="report_ai_provider" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm bg-white outline-none focus:ring-2 focus:ring-primary/25">
@foreach(\App\Enums\ReportAiVendor::cases() as $raiVendor)
<option value="{{ $raiVendor->value }}" @selected($raiProv === $raiVendor->value)>{{ $raiVendor->label() }}</option>
@endforeach
</select>
<p class="text-[11px] text-on-surface-variant mt-1">OpenAI / DeepSeek use the standard chat completions API. Claude uses Messages. Gemini uses <span class="font-mono text-[10px]">generateContent</span>.</p>
</div>
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface">Model name</label>
<input type="text" name="report_ai_model" value="{{ old('report_ai_model', $siteSetting->report_ai_model) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25 font-mono" placeholder="Leave blank to use starter model for the provider"/>
<p class="text-[11px] text-on-surface-variant mt-1">Examples: OpenAI&nbsp;<span class="font-mono text-[10px]">gpt-4o-mini</span>, Claude&nbsp;<span class="font-mono text-[10px]">claude-3-5-sonnet-20241022</span>, Gemini&nbsp;<span class="font-mono text-[10px]">gemini-2.0-flash</span>, DeepSeek&nbsp;<span class="font-mono text-[10px]">deepseek-chat</span>.</p>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">API base URL override (optional)</label>
<input type="text" name="report_ai_api_base_url_override" value="{{ old('report_ai_api_base_url_override', $siteSetting->report_ai_api_base_url_override) }}" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25 font-mono" placeholder="https://api.openai.com/v1"/>
<p class="text-[11px] text-on-surface-variant mt-1">
Use the REST root for each platform (no trailing path): OpenAI-compatible includes <span class="font-mono text-[10px]">…/v1</span>; Anthropic <span class="font-mono text-[10px]">https://api.anthropic.com/v1</span>; Gemini <span class="font-mono text-[10px]">https://generativelanguage.googleapis.com/v1beta</span>. Leave blank to apply the curated default for your provider selection.
</p>
</div>
<div class="space-y-1 md:col-span-2">
<label class="text-label-md font-semibold text-on-surface">API key</label>
<input type="password" name="report_ai_api_key" autocomplete="new-password" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm font-mono outline-none focus:ring-2 focus:ring-primary/25" placeholder="Paste key — leave blank to keep current encrypted value"/>
<p class="text-[11px] text-on-surface-variant mt-1">OpenAI-compatible and DeepSeek: Bearer tokens. Claude: Anthropic secret key from the console. Gemini: AI Studio / Google AI API key (sent as query key).</p>
</div>
</div>
</div>
</div>
<div class="flex justify-end gap-3">
<button type="submit" class="px-8 py-3 rounded-xl bg-primary text-white font-bold text-body-md hover:bg-primary-container shadow-lg shadow-primary/20 transition-all">Save settings</button>
</div>
</form>
</div>
@push('scripts')
<script>
(function () {
  var themePicker = document.getElementById('frontend-theme-picker');
  if (themePicker) {
    function syncThemeCards() {
      themePicker.querySelectorAll('.frontend-theme-card').forEach(function (card) {
        var input = card.querySelector('input[type="radio"]');
        var selected = input && input.checked;
        card.classList.toggle('border-primary', selected);
        card.classList.toggle('ring-2', selected);
        card.classList.toggle('ring-primary/20', selected);
        var check = card.querySelector('.frontend-theme-check');
        if (check) {
          check.classList.toggle('hidden', !selected);
          check.classList.toggle('inline-flex', selected);
        }
      });
    }
    themePicker.addEventListener('change', syncThemeCards);
    syncThemeCards();
  }
})();

(function () {
  var picker = document.getElementById('settings-section-picker');
  var hidden = document.getElementById('settings_section_field');
  if (!picker || !hidden) return;
  var panels = document.querySelectorAll('.settings-panel');
  var keys = ['site', 'home', 'admin', 'email', 'payments', 'ai_reports'];
  function show(key) {
    if (keys.indexOf(key) === -1) key = 'site';
    panels.forEach(function (el) {
      if (el.getAttribute('data-panel') === key) el.classList.remove('hidden');
      else el.classList.add('hidden');
    });
    hidden.value = key;
    picker.value = key;
    try {
      var u = new URL(window.location.href);
      u.searchParams.set('section', key);
      var qs = u.searchParams.toString();
      window.history.replaceState({}, '', u.pathname + (qs ? '?' + qs : ''));
    } catch (e) {}
  }
  picker.addEventListener('change', function () {
    show(picker.value);
  });
})();

(function () {
  var container = document.getElementById('social-media-sortable');
  if (!container) return;

  var dragRow = null;

  function clearDragState() {
    dragRow = null;
    container.querySelectorAll('.social-sort-row').forEach(function (row) {
      row.classList.remove('ring-2', 'ring-primary/30', 'opacity-60');
    });
  }

  function bindDrag(row) {
    var handle = row.querySelector('.social-drag-handle');
    if (!handle) return;

    handle.addEventListener('dragstart', function (e) {
      dragRow = row;
      row.classList.add('opacity-60');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', 'social');
    });

    handle.addEventListener('dragend', function () {
      clearDragState();
    });
  }

  container.querySelectorAll('.social-sort-row').forEach(bindDrag);

  container.addEventListener('dragover', function (e) {
    if (!dragRow) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    var target = e.target.closest('.social-sort-row');
    if (!target || target === dragRow) return;
    container.querySelectorAll('.social-sort-row').forEach(function (row) {
      row.classList.toggle('ring-2', row === target);
      row.classList.toggle('ring-primary/30', row === target);
    });
    var rect = target.getBoundingClientRect();
    var before = e.clientY < rect.top + rect.height / 2;
    if (before) {
      container.insertBefore(dragRow, target);
    } else {
      container.insertBefore(dragRow, target.nextSibling);
    }
  });

  container.addEventListener('drop', function (e) {
    e.preventDefault();
    clearDragState();
  });
})();
</script>
<style>
#social-media-sortable .social-sort-row.ring-2 { border-color: color-mix(in srgb, var(--color-primary, #7c3aed) 40%, transparent); }
#frontend-theme-picker .frontend-theme-card.border-primary { border-color: var(--color-primary, #7c3aed); }
</style>
@endpush
@endsection
