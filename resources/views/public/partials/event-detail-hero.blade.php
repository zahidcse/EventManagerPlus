@php
  $hero = $hero ?? ($galleryUrls->first() ?: 'https://images.unsplash.com/photo-1506157786151-b8491531f063?w=1600&q=80');
  $addrOrLoc = trim((string) ($addrOrLoc ?? ($event->fullVenueAddressLine() !== '' ? $event->fullVenueAddressLine() : $event->locationLabel())));
  $categoryLabel = $event->categoryLabel() ?: 'Event';
  $heroSubtitle = \Illuminate\Support\Str::limit(trim(strip_tags($event->description ?: '')), 120);
  $heroWhenLine = $event->detailHeroWhenLine();
  $joinedCount = (int) $event->registrations_count;
@endphp

<div class="hero hero--detail">
  <img id="heroImg" src="{{ $hero }}" alt="{{ $event->title }}" />
  <div class="overlay" aria-hidden="true"></div>

  <div class="hero-actions" aria-label="Event actions">
    <button type="button" class="hero-action-btn" data-share-event title="Share event" aria-label="Share event">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
    </button>
  </div>

  <div class="hero-content">
    <div class="hero-tags">
      <span class="hero-tag hero-tag--primary">{{ $categoryLabel }}</span>
    </div>

    <h1>{{ $event->title }}</h1>

    @if($heroSubtitle !== '')
      <p class="hero-lede">{{ $heroSubtitle }}</p>
    @endif

    <div class="hero-meta hero-meta--detail" aria-label="Event summary">
      <div class="hero-meta-detail-row">
        @if($heroWhenLine !== '')
          <span class="hero-meta-item hero-meta-item--when">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span class="hero-meta-item-text">{{ $heroWhenLine }}</span>
          </span>
        @endif
        @if($addrOrLoc !== '')
          <span class="hero-meta-sep" aria-hidden="true">·</span>
          <span class="hero-meta-item hero-meta-item--location">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span class="hero-meta-item-text">{{ $addrOrLoc }}</span>
          </span>
        @endif
        <span class="hero-meta-item hero-meta-item--joined">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          {{ number_format($joinedCount) }} joined
        </span>
      </div>
    </div>
  </div>
</div>
