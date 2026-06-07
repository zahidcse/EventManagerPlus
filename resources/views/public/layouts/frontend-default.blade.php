<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
@include('partials.site-favicon')
<title>@yield('title', $siteName)</title>
@if(trim($__env->yieldContent('meta_description')) !== '')
<meta name="description" content="@yield('meta_description')" />
@endif
@stack('head')
<link rel="stylesheet" href="{{ asset('themes/event-public.css') }}" />
@stack('styles')
<script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="default-site ep-theme-default">

<header class="default-nav">
  <div class="default-nav-inner">
    <a href="{{ url('/') }}" class="default-nav-brand {{ ! empty($siteLogoUrl) ? 'default-nav-brand--logo' : '' }}">
      @if(! empty($siteLogoUrl))
      <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="default-nav-brand-img" width="200" height="40" decoding="async" />
      @else
      {{ $siteName }}
      @endif
    </a>
    <nav aria-label="Primary">
      <a href="{{ url('/') }}">Home</a>
      <a href="{{ route('events.index') }}">Events</a>
      <a href="{{ route('blog.index') }}">Blog</a>
      @auth
        @unless(Auth::user()->is_admin)
          <a href="{{ route('account.index') }}">My account</a>
        @endunless
        @if(Auth::user()->is_admin)
          <a href="{{ route('admin.dashboard') }}">Staff</a>
        @endif
      @else
        <a href="{{ route('login') }}">Sign in</a>
        <a href="{{ route('register') }}">Join</a>
      @endauth
      <a href="{{ route('admin.login') }}">Admin</a>
    </nav>
    <div style="display:flex;align-items:center;gap:12px;margin-left:auto">
      @auth
        <form method="post" action="{{ route('logout') }}" style="margin:0">
          @csrf
          <button type="submit" class="default-signout-btn" style="cursor:pointer;background:none;border:none;padding:0;font:inherit;color:inherit;font-weight:600">Log out</button>
        </form>
      @endauth
    </div>
  </div>
</header>

<main class="ep-layout">
@yield('content')
</main>

<footer class="default-footer">
  <div class="default-footer-inner">
    <div class="default-footer-brand {{ ! empty($siteLogoUrl) ? 'default-footer-brand--has-logo' : '' }}">
      @if(! empty($siteLogoUrl))
      <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="default-footer-brand-img" width="200" height="40" decoding="async" />
      @else
      <span class="default-footer-brand-title">{{ $siteName }}</span>
      @endif
    </div>
    @if(count($socialLinks ?? []) > 0)
    <div class="default-footer-social">
      @include('public.partials.footer-social-links')
    </div>
    @endif
    <div class="default-footer-section">
      <div class="default-footer-heading">Information</div>
      <nav class="default-footer-info-links" aria-label="Information">
        <a href="{{ route('blog.index') }}">Blog</a>
        @foreach($informationFooterPages as $footerPage)
          <a href="{{ route('pages.show', $footerPage) }}">{{ $footerPage->title }}</a>
        @endforeach
      </nav>
    </div>
    <div class="default-footer-copy">
      @include('public.partials.footer-copyright')
    </div>
  </div>
</footer>

<script>
if (window.lucide) { lucide.createIcons(); }
</script>
<script src="{{ asset('js/searchable-select.js') }}" defer></script>
@stack('scripts')
</body>
</html>
