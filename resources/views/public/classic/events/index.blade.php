@extends('public.classic.layouts.app')

@php
    use App\Support\PublicFrontendTheme;
    $isClassicLight = PublicFrontendTheme::isClassicLight();
@endphp

@php
    $listDescription = 'Browse upcoming public events and book tickets.';
@endphp

@section('title', 'All events — '.$siteName)
@section('meta_description', $listDescription)

@section('content')
@php
    $placeholders = [
        'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=800&q=80',
        'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&q=80',
        'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=800&q=80',
    ];
@endphp

  <div class="page-head">
    <p class="kicker">All events</p>
    <h1 class="title">Find your next night out</h1>
    <p class="lede">{{ $listDescription }}</p>
  </div>

  @if($isClassicLight)
  <form class="search classic-search-wrap" method="get" action="{{ route('events.index') }}">
    <div class="search-field"><i data-lucide="search" class="icon-sm"></i><input type="search" name="q" placeholder="Search by title…" value="{{ request('q') }}" /></div>
    <div class="search-field"><i data-lucide="map-pin" class="icon-sm"></i><input name="city" placeholder="City / state" value="{{ request('city') }}" /></div>
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
  @else
  <form class="search classic-search-wrap" method="get" action="{{ route('events.index') }}">
    <div class="search-field"><i data-lucide="search" class="icon-sm" style="color:#a39bb8"></i><input type="search" name="q" placeholder="Search by title…" value="{{ request('q') }}" /></div>
    <div class="search-field"><i data-lucide="map-pin" class="icon-sm" style="color:#a39bb8"></i><input name="city" placeholder="City / state" value="{{ request('city') }}" /></div>
    <div class="search-field"><i data-lucide="calendar" class="icon-sm" style="color:#a39bb8"></i><input type="date" name="date" value="{{ request('date') }}" /></div>
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
  @endif

  <div class="events{{ $isClassicLight ? ' events--list' : '' }}" @unless($isClassicLight) style="margin-top:40px" @endunless>
    @forelse($events as $idx => $event)
      @php
        $img = $event->cover_image_path ? asset('uploads/'.$event->cover_image_path) : ($placeholders[$idx % count($placeholders)] ?? $placeholders[0]);
        $low = $event->tickets->min(fn ($t) => $t->effectiveUnitPrice());
        $priceLabel = $low !== null ? 'From $'.number_format((float) $low, 0) : 'Pricing on request';
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
          <span class="btn btn-primary btn-book">View details</span>
        </div>
      </a>
      @else
      <a href="{{ route('events.show', $event) }}" class="event" style="text-decoration:none;color:inherit;display:block">
        <div class="event-img">
          <img src="{{ $img }}" alt="{{ $event->title }}" loading="lazy" />
          <span class="event-tag">{{ $event->categoryLabel() ?: 'Event' }}</span>
        </div>
        <div class="event-body">
          <h3>{{ $event->title }}</h3>
          <div class="event-meta"><i data-lucide="calendar" class="icon-sm"></i> {{ $when }}</div>
          <div class="event-meta"><i data-lucide="map-pin" class="icon-sm"></i> {{ $event->locationLabel() }}</div>
          <div class="event-foot"><span class="event-price text-gradient">{{ $priceLabel }}</span><span class="btn btn-primary" style="pointer-events:none">View details</span></div>
        </div>
      </a>
      @endif
    @empty
      <p @unless($isClassicLight) style="grid-column:1/-1;color:var(--muted);text-align:center;padding:48px 16px" @else class="events-empty" @endunless>No upcoming public events match your filters. Try clearing search or check back soon.</p>
    @endforelse
  </div>

{{ $events->withQueryString()->onEachSide(1)->links('pagination.ep') }}
@endsection
