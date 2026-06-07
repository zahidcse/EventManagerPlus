@php
  $sidebarWhen = $sidebarWhen ?? $event->sidebarWhenLines();
  $sidebarDateLine = trim((string) ($sidebarWhen['date'] ?? ''));
  $sidebarTimeLine = trim((string) ($sidebarWhen['time'] ?? ''));
  $addrOrLoc = trim((string) ($addrOrLoc ?? ''));
  $hasWhen = $sidebarDateLine !== '' || $sidebarTimeLine !== '';
@endphp
@if($hasWhen || $addrOrLoc !== '')
  <div class="sidebar-event-info" aria-label="Event details">
    @if($sidebarDateLine !== '')
      <div class="sidebar-event-info-row">
        <span class="sidebar-event-info-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </span>
        <span class="sidebar-event-info-text">{{ $sidebarDateLine }}</span>
      </div>
    @endif
    @if($sidebarTimeLine !== '')
      <div class="sidebar-event-info-row">
        <span class="sidebar-event-info-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </span>
        <span class="sidebar-event-info-text">{{ $sidebarTimeLine }}</span>
      </div>
    @endif
    @if($addrOrLoc !== '')
      <div class="sidebar-event-info-row">
        <span class="sidebar-event-info-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        </span>
        <span class="sidebar-event-info-text">{!! nl2br(e($addrOrLoc)) !!}</span>
      </div>
    @endif
  </div>
@endif
