@if($event->shouldShowTimezoneNotice())
  <p class="event-timezone-notice" role="note" style="margin:0.5rem 0 0;font-size:0.875rem;opacity:0.85;">
    {{ $event->timezoneNoticeMessage() }}
  </p>
@endif
