<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  @include('partials.site-favicon')
  <title>@yield('title', $siteName)</title>
  @if(trim($__env->yieldContent('meta_description')) !== '')
    <meta name="description" content="@yield('meta_description')" />
  @endif
  @stack('head')
  <link rel="stylesheet" href="{{ asset(\App\Support\PublicFrontendTheme::stylesheet()) }}" />
  <link rel="stylesheet" href="{{ asset('themes/event-public.css') }}" />
  @if(\App\Support\PublicFrontendTheme::isClassicLight())
  <link rel="stylesheet" href="{{ asset('themes/classic-light/ep-pages.css') }}" />
  <link rel="stylesheet" href="{{ asset('themes/classic-light/event-overrides.css') }}" />
  @endif
  @stack('styles')
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="{{ \App\Support\PublicFrontendTheme::bodyClass() }}">

  @include('public.partials.classic-header')

  <main class="ep-layout {{ \App\Support\PublicFrontendTheme::layoutThemeClass() }}">
    @yield('content')
  </main>

  @include('public.partials.classic-footer')

  <script>
    document.querySelectorAll('[data-nav-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var d = document.querySelector('[data-nav-drawer]');
        if (!d) return;
        var open = d.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    });
    if (window.lucide) { lucide.createIcons(); }
  </script>
  <script src="{{ asset('js/searchable-select.js') }}" defer></script>
  @stack('scripts')
</body>

</html>