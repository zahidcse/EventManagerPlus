@php
    use App\Support\PublicFrontendTheme;

    $isClassicLight = PublicFrontendTheme::isClassicLight();
    $placeholders = [
        'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=800&q=80',
        'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&q=80',
        'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=800&q=80',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
@include('partials.site-favicon')
<title>{{ $siteSetting->homeMetaTitle($siteName) }}</title>
<meta name="description" content="{{ $siteSetting->homeMetaDescription() }}" />
<link rel="stylesheet" href="{{ asset(\App\Support\PublicFrontendTheme::stylesheet()) }}" />
<script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="{{ \App\Support\PublicFrontendTheme::bodyClass() }}">

<header class="nav{{ $isClassicLight ? ' nav--marketing' : '' }}">
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
      <a href="#how">How it works</a>
      <a href="#faq">FAQ</a>
      <a href="#contact">Contact</a>
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
        @unless($isClassicLight)
        <a href="{{ route('register') }}" class="btn btn-outline">Join</a>
        @endunless
      @endauth
      <a href="{{ route('events.index') }}" class="btn btn-primary">Get Tickets</a>
    </div>
    <button type="button" class="menu-btn" aria-label="Menu" aria-expanded="false" data-nav-toggle><i data-lucide="menu"></i></button>
    <div class="nav-drawer" id="nav-drawer" data-nav-drawer>
      <a href="{{ url('/') }}">Home</a>
      <a href="{{ route('events.index') }}">Events</a>
      <a href="#how">How it works</a>
      <a href="#faq">FAQ</a>
      <a href="#contact">Contact</a>
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
          @unless($isClassicLight)
          <a href="{{ route('register') }}" class="btn btn-outline" style="justify-content:center;width:100%">Join</a>
          @endunless
        @endauth
        <a href="{{ route('events.index') }}" class="btn btn-primary" style="justify-content:center;width:100%">Get Tickets</a>
      </div>
    </div>
  </div>
</header>

@if($isClassicLight)
<section class="hero hero--marketing">
  <div class="hero-marketing-wash" aria-hidden="true"></div>
  <div class="container hero-marketing-grid">
    <div class="hero-content">
      <div class="badge badge--marketing"><i data-lucide="sparkles" class="icon-sm"></i> {{ $siteSetting->homeField('home_hero_badge') }}</div>
      <h1>{{ $siteSetting->homeField('home_hero_headline_before') }} <span class="text-accent">{{ $siteSetting->homeField('home_hero_headline_highlight') }}</span> {{ $siteSetting->homeField('home_hero_headline_suffix') }}</h1>
      <p class="lead">{{ $siteSetting->homeField('home_hero_lead') }}</p>
      <div class="hero-cta">
        <a href="{{ route('events.index') }}" class="btn btn-primary btn-lg">{{ $siteSetting->homeField('home_hero_cta_primary_label') }}</a>
        <a href="#how" class="btn btn-outline btn-lg">{{ $siteSetting->homeField('home_hero_cta_secondary_label') }}</a>
      </div>
      <form class="search search--marketing" action="{{ url('/') }}" method="get">
        <div class="search-field"><i data-lucide="search" class="icon-sm"></i><input type="search" name="q" placeholder="Search events, artists..." value="{{ request('q') }}" /></div>
        <div class="search-field"><i data-lucide="map-pin" class="icon-sm"></i><input name="city" placeholder="Location" value="{{ request('city') }}" /></div>
        <button type="submit" class="btn btn-primary search-submit">Search</button>
      </form>
      <div class="stats stats--marketing">
        <div><div class="stat-v">{{ $featuredEvents->count() > 0 ? $featuredEvents->count().'+' : '—' }}</div><div class="stat-l">{{ $siteSetting->homeField('home_hero_stat_1_label') }}</div></div>
        <div><div class="stat-v">{{ $siteSetting->homeField('home_hero_stat_2_value') }}</div><div class="stat-l">{{ $siteSetting->homeField('home_hero_stat_2_label') }}</div></div>
        <div><div class="stat-v">{{ $siteSetting->homeField('home_hero_stat_3_value') }}</div><div class="stat-l">{{ $siteSetting->homeField('home_hero_stat_3_label') }}</div></div>
      </div>
    </div>
    <div class="hero-visual">
      <img src="{{ $heroImageUrl }}" alt="" class="hero-visual-img" />
    </div>
  </div>
</section>
@else
<section class="hero">
  <img class="hero-bg" src="{{ $heroImageUrl }}" alt="" />
  <div class="hero-overlay"></div>
  <div class="hero-glow"></div>
  <div class="container">
   <div class="hero-content">
    <div class="badge"><i data-lucide="sparkles" class="icon-sm" style="color:#f472b6"></i> {{ $siteSetting->homeField('home_hero_badge') }}</div>
    <h1>{{ $siteSetting->homeField('home_hero_headline_before') }} <span class="text-gradient">{{ $siteSetting->homeField('home_hero_headline_highlight') }}</span> {{ $siteSetting->homeField('home_hero_headline_suffix') }}</h1>
    <p class="lead">{{ $siteSetting->homeField('home_hero_lead') }}</p>
    <div class="hero-cta">
      <a href="{{ route('events.index') }}" class="btn btn-primary btn-lg">{{ $siteSetting->homeField('home_hero_cta_primary_label') }}</a>
      <a href="#how" class="btn btn-outline btn-lg">{{ $siteSetting->homeField('home_hero_cta_secondary_label') }}</a>
    </div>
    <form class="search" action="{{ url('/') }}" method="get">
      <div class="search-field"><i data-lucide="search" class="icon-sm" style="color:#a39bb8"></i><input type="search" name="q" placeholder="Search events, artists..." value="{{ request('q') }}" /></div>
      <div class="search-field"><i data-lucide="map-pin" class="icon-sm" style="color:#a39bb8"></i><input name="city" placeholder="Location" value="{{ request('city') }}" /></div>
      <div class="search-field"><i data-lucide="calendar" class="icon-sm" style="color:#a39bb8"></i><input type="date" name="date" value="{{ request('date') }}" /></div>
      <button type="submit" class="btn btn-primary">Search</button>
    </form>
    <div class="stats">
      <div><div class="stat-v text-gradient">{{ $featuredEvents->count() > 0 ? $featuredEvents->count().'+' : '—' }}</div><div class="stat-l">{{ $siteSetting->homeField('home_hero_stat_1_label') }}</div></div>
      <div><div class="stat-v text-gradient">{{ $siteSetting->homeField('home_hero_stat_2_value') }}</div><div class="stat-l">{{ $siteSetting->homeField('home_hero_stat_2_label') }}</div></div>
      <div><div class="stat-v text-gradient">{{ $siteSetting->homeField('home_hero_stat_3_value') }}</div><div class="stat-l">{{ $siteSetting->homeField('home_hero_stat_3_label') }}</div></div>
    </div>
   </div>
  </div>
</section>
@endif

<section class="block" id="how">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">{{ $siteSetting->homeField('home_how_eyebrow') }}</div>
      <h2>{{ $siteSetting->homeField('home_how_title_before') }} <span class="text-gradient">{{ $siteSetting->homeField('home_how_title_highlight') }}</span></h2>
    </div>
    <div class="steps">
      <div class="step"><div class="step-num">01</div><div class="step-icon"><i data-lucide="search" class="icon-lg" style="color:#fff"></i></div><h3>{{ $siteSetting->homeField('home_how_step1_title') }}</h3><p>{{ $siteSetting->homeField('home_how_step1_description') }}</p></div>
      <div class="step"><div class="step-num">02</div><div class="step-icon"><i data-lucide="ticket" class="icon-lg" style="color:#fff"></i></div><h3>{{ $siteSetting->homeField('home_how_step2_title') }}</h3><p>{{ $siteSetting->homeField('home_how_step2_description') }}</p></div>
      <div class="step"><div class="step-num">03</div><div class="step-icon"><i data-lucide="party-popper" class="icon-lg" style="color:#fff"></i></div><h3>{{ $siteSetting->homeField('home_how_step3_title') }}</h3><p>{{ $siteSetting->homeField('home_how_step3_description') }}</p></div>
    </div>
  </div>
</section>

<section class="block featured{{ $isClassicLight ? ' featured--marketing' : '' }}" id="events">
  <div class="container">
    <div class="featured-head{{ $isClassicLight ? ' featured-head--marketing' : '' }}">
      <div>
        <div class="eyebrow">Upcoming</div>
        <h2 @unless($isClassicLight) style="font-size:clamp(32px,4vw,52px);font-weight:800;letter-spacing:-.02em" @endunless>Featured events</h2>
      </div>
      <a href="{{ route('events.index') }}" class="{{ $isClassicLight ? 'featured-view-all' : 'btn btn-ghost' }}">View all <i data-lucide="arrow-right" class="icon-sm"></i></a>
    </div>
    <div class="events{{ $isClassicLight ? ' events--list' : '' }}">
      @forelse($featuredEvents as $idx => $event)
        @php
          $img = $event->cover_image_path ? asset('uploads/'.$event->cover_image_path) : ($placeholders[$idx % count($placeholders)] ?? $placeholders[0]);
          $low = $event->tickets->min(fn ($t) => $t->effectiveUnitPrice());
          $priceLabel = $low !== null && $low !== '' ? 'From $'.number_format((float) $low, 0) : 'Pricing on request';
          $when = $event->scheduleSummaryLine();
        @endphp
        @if($isClassicLight)
        <a href="{{ route('events.show', $event) }}" class="event event--row">
          <div class="event-row-media">
            <img src="{{ $img }}" alt="{{ $event->title }}" loading="lazy" />
          </div>
          <div class="event-row-main">
            <span class="event-tag event-tag--pill">{{ $event->categoryLabel() ?: 'Event' }}</span>
            <h3 class="event-row-title">{{ $event->title }}</h3>
            <div class="event-row-meta">
              <span class="event-row-meta-item"><i data-lucide="calendar" class="icon-sm"></i>{{ $when }}</span>
              <span class="event-row-meta-item"><i data-lucide="map-pin" class="icon-sm"></i>{{ $event->locationLabel() }}</span>
            </div>
          </div>
          <div class="event-row-aside">
            <span class="event-price">{{ $priceLabel }}</span>
            <span class="btn btn-primary btn-book">Book now</span>
          </div>
        </a>
        @else
        <a href="{{ route('events.show', $event) }}" class="event" style="text-decoration:none;color:inherit;display:block">
          <div class="event-img">
            <img src="{{ $img }}" alt="{{ $event->title }}" loading="lazy"/>
            <span class="event-tag">{{ $event->categoryLabel() ?: 'Event' }}</span>
          </div>
          <div class="event-body">
            <h3>{{ $event->title }}</h3>
            <div class="event-meta"><i data-lucide="calendar" class="icon-sm"></i> {{ $when }}</div>
            <div class="event-meta"><i data-lucide="map-pin" class="icon-sm"></i> {{ $event->locationLabel() }}</div>
            <div class="event-foot"><span class="event-price text-gradient">{{ $priceLabel }}</span><span class="btn btn-primary" style="pointer-events:none">Book now</span></div>
          </div>
        </a>
        @endif
      @empty
        <p @unless($isClassicLight) style="grid-column:1/-1;color:var(--muted);text-align:center;padding:48px 16px" @else class="events-empty" @endunless>Public events will appear here once published in the admin. Try adding active, upcoming events with public visibility.</p>
      @endforelse
    </div>
  </div>
</section>

<section class="block" id="faq">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">{{ $siteSetting->homeField('home_faq_eyebrow') }}</div>
      <h2>{{ $siteSetting->homeField('home_faq_title_before') }} <span class="text-gradient">{{ $siteSetting->homeField('home_faq_title_highlight') }}</span></h2>
    </div>
    <div class="faq-wrap">
      @forelse($homeFaqs as $i => $faq)
      <details class="faq-item" @if($i === 0) open @endif><summary>{{ $faq->question }}</summary><div class="faq-body">{!! nl2br(e($faq->answer)) !!}</div></details>
      @empty
      <p class="text-body-md text-center" style="color:var(--muted);padding:24px 0">No FAQs yet. Add them under Admin → Settings → Home settings.</p>
      @endforelse
    </div>
  </div>
</section>

<section class="block" id="contact">
  <div class="container">
    <div class="contact-card">
      <div class="contact-grid">
        <div>
          <div class="eyebrow">{{ $siteSetting->homeField('home_contact_eyebrow') }}</div>
          <h2 style="font-size:clamp(32px,4vw,52px);font-weight:800;letter-spacing:-.02em">{{ $siteSetting->homeField('home_contact_title_before') }} <span class="text-gradient">{{ $siteSetting->homeField('home_contact_title_highlight') }}</span></h2>
          <p style="margin-top:16px;color:var(--muted);max-width:420px">{{ $siteSetting->homeField('home_contact_lead') }}</p>
          <div class="contact-cta-btns">
            @if($contactEmail)
              <a class="btn btn-primary btn-lg" href="mailto:{{ $contactEmail }}"><i data-lucide="message-circle" class="icon-sm"></i> Email us</a>
            @endif
            @if($contactPhone)
              <a class="btn btn-outline btn-lg" href="tel:{{ preg_replace('/\s+/', '', $contactPhone) }}">Call</a>
            @endif
          </div>
        </div>
        <div class="contact-form">
          <div class="row"><input id="cf-name" placeholder="Your name"/><input id="cf-email" type="email" placeholder="Email"/></div>
          <input id="cf-subject" placeholder="Subject"/>
          <textarea id="cf-body" rows="4" placeholder="How can we help?"></textarea>
          @if($contactEmail)
            <button type="button" class="btn btn-primary" id="cf-send" style="width:100%" data-email="{{ $contactEmail }}">Send message</button>
          @else
            <button type="button" class="btn btn-primary" style="width:100%" disabled>Configure contact email in admin</button>
          @endif
          <div class="contact-info">
            @if($contactEmail)<span><i data-lucide="mail" class="icon-sm"></i>{{ $contactEmail }}</span>@endif
            @if($contactPhone)<span><i data-lucide="phone" class="icon-sm"></i>{{ $contactPhone }}</span>@endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<footer>
  <div class="container">
    <div class="foot-grid">
      <div class="foot-about">
        <div class="brand {{ filled($siteLogoUrl ?? null) ? 'brand-has-logo' : '' }}">
          @if(filled($siteLogoUrl ?? null))
          <span class="brand-logo"><img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" decoding="async" /></span>
          @else
          <span class="brand-mark">
            <i data-lucide="ticket" class="icon" style="color:#fff"></i>
          </span>
          {{ $siteName }}
          @endif
        </div>
        <p>Discover and book tickets for memorable live experiences.</p>
        @include('public.partials.footer-social-links')
      </div>
      <div class="foot-col"><h4>Discover</h4><ul><li><a href="{{ route('events.index') }}">Events</a></li><li><a href="#how">How it works</a></li><li><a href="#faq">FAQ</a></li><li><a href="#contact">Contact</a></li></ul></div>
      <div class="foot-col"><h4>Support</h4><ul><li><a href="#contact">Contact</a></li><li><a href="#faq">Help</a></li></ul></div>
    </div>
    <div class="foot-bot">@include('public.partials.footer-copyright')</div>
  </div>
</footer>

<script>
document.querySelectorAll('[data-nav-toggle]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var d = document.querySelector('[data-nav-drawer]');
    if (!d) return;
    var open = d.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
});
var sendBtn = document.getElementById('cf-send');
if (sendBtn && sendBtn.dataset.email) {
  sendBtn.addEventListener('click', function() {
    var to = sendBtn.dataset.email;
    var sub = document.getElementById('cf-subject');
    var bodyEl = document.getElementById('cf-body');
    var name = document.getElementById('cf-name');
    var em = document.getElementById('cf-email');
    var subj = (sub && sub.value) ? sub.value : 'Message from website';
    var lines = [];
    if (name && name.value) { lines.push('From: ' + name.value); }
    if (em && em.value) { lines.push('Reply: ' + em.value); }
    if (bodyEl && bodyEl.value) { lines.push(bodyEl.value); }
    var text = lines.join('\n');
    window.location.href = 'mailto:' + to + '?subject=' + encodeURIComponent(subj) + '&body=' + encodeURIComponent(text);
  });
}
if (window.lucide) { lucide.createIcons(); }
</script>
</body>
</html>
