@extends('public.layouts.frontend-default')

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

  <form class="search-bar" method="get" action="{{ route('events.index') }}">
    <input type="search" name="q" placeholder="Search by title…" value="{{ request('q') }}" />
    <input name="city" placeholder="City / state" value="{{ request('city') }}" />
    <input type="date" name="date" value="{{ request('date') }}" />
    <button type="submit" class="btn">Search</button>
  </form>

  <div class="grid">
    @forelse($events as $idx => $event)
      @php
        $img = $event->cover_image_path ? asset('uploads/'.$event->cover_image_path) : ($placeholders[$idx % count($placeholders)] ?? $placeholders[0]);
        $low = $event->tickets->min(fn ($t) => $t->effectiveUnitPrice());
        $priceLabel = $low !== null ? 'From $'.number_format((float) $low, 0) : 'Pricing on request';
        $when = $event->scheduleSummaryLine();
      @endphp
      <a class="event-card" href="{{ route('events.show', $event) }}">
        <div class="img-wrap">
          <img src="{{ $img }}" alt="{{ $event->title }}" loading="lazy" />
          <span class="badge">{{ $event->categoryLabel() ?: 'Event' }}</span>
        </div>
        <div class="body">
          <h3>{{ $event->title }}</h3>
          <div class="meta">
            <div class="meta-row"><span aria-hidden="true">📅</span> {{ $when }}</div>
            <div class="meta-row"><span aria-hidden="true">📍</span> {{ $event->locationLabel() }}</div>
          </div>
          <div class="footer"><span class="price">{{ $priceLabel }}</span><span class="btn" style="padding:6px 14px;font-size:12px;">View details</span></div>
        </div>
      </a>
    @empty
      <p class="body-text" style="grid-column:1/-1;text-align:center;padding:48px 0">No upcoming public events match your filters. Try clearing search or check back soon.</p>
    @endforelse
  </div>

{{ $events->withQueryString()->onEachSide(1)->links('pagination.ep') }}
@endsection
