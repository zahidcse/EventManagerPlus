<header class="nav{{ \App\Support\PublicFrontendTheme::isClassicLight() ? ' nav--marketing' : '' }}">
  <div class="container nav-inner">
    <a href="{{ url('/') }}" class="brand {{ filled($siteLogoUrl ?? null) ? 'brand-has-logo' : '' }}">
      @if(filled($siteLogoUrl ?? null))
        <span class="brand-logo"><img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" decoding="async" /></span>
      @else
        <span class="brand-mark">
          <i data-lucide="ticket" class="icon" style="color:#fff"></i>
        </span>
        {{ $siteName }}
      @endif
    </a>
    <nav class="nav-links" aria-label="Primary">
      <a href="{{ url('/') }}">Home</a>
      <a href="{{ route('events.index') }}">Events</a>
      <a href="{{ route('blog.index') }}">Blog</a>
      <a href="{{ url('/#how') }}">How it works</a>
      <a href="{{ url('/#faq') }}">FAQ</a>
      <a href="{{ url('/#contact') }}">Contact</a>
    </nav>
    <div class="nav-actions">
      @auth
        @if(Auth::user()->is_admin)
          <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost">Staff</a>
          <form method="post" action="{{ route('logout') }}" style="margin:0;display:inline">
            @csrf
            <button type="submit" class="btn btn-ghost">Log out</button>
          </form>
        @else
          <a href="{{ route('account.index') }}" class="btn btn-ghost">My account</a>
          <form method="post" action="{{ route('logout') }}" style="margin:0;display:inline">
            @csrf
            <button type="submit" class="btn btn-ghost">Log out</button>
          </form>
        @endif
      @else
        <a href="{{ route('login') }}" class="btn btn-ghost">Sign in</a>
        @if(\App\Support\PublicFrontendTheme::isClassicDark())
        <a href="{{ route('register') }}" class="btn btn-outline">Join</a>
        @endif
      @endauth
      <a href="{{ route('events.index') }}" class="btn btn-primary">Get Tickets</a>
    </div>
    <button type="button" class="menu-btn" aria-label="Menu" aria-expanded="false" data-nav-toggle><i data-lucide="menu"></i></button>
    <div class="nav-drawer" id="nav-drawer" data-nav-drawer>
      <a href="{{ url('/') }}">Home</a>
      <a href="{{ route('events.index') }}">Events</a>
      <a href="{{ route('blog.index') }}">Blog</a>
      <a href="{{ url('/#how') }}">How it works</a>
      <a href="{{ url('/#faq') }}">FAQ</a>
      <a href="{{ url('/#contact') }}">Contact</a>
      <div class="drawer-actions">
        @auth
          @if(Auth::user()->is_admin)
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline" style="justify-content:center;width:100%">Staff dashboard</a>
            <form method="post" action="{{ route('logout') }}" style="margin:0">
              @csrf
              <button type="submit" class="btn btn-outline" style="justify-content:center;width:100%">Log out</button>
            </form>
          @else
            <a href="{{ route('account.index') }}" class="btn btn-ghost" style="justify-content:center;width:100%">My account</a>
            <div style="padding:8px 0;font-size:13px;color:var(--muted);text-align:center">{{ Auth::user()->name }}</div>
            <form method="post" action="{{ route('logout') }}" style="margin:0">
              @csrf
              <button type="submit" class="btn btn-outline" style="justify-content:center;width:100%">Log out</button>
            </form>
          @endif
        @else
          <a href="{{ route('login') }}" class="btn btn-ghost" style="justify-content:center;width:100%">Sign in</a>
          @if(\App\Support\PublicFrontendTheme::isClassicDark())
          <a href="{{ route('register') }}" class="btn btn-outline" style="justify-content:center;width:100%">Join</a>
          @endif
        @endauth
        <a href="{{ route('events.index') }}" class="btn btn-primary" style="justify-content:center;width:100%">Get Tickets</a>
      </div>
    </div>
  </div>
</header>
