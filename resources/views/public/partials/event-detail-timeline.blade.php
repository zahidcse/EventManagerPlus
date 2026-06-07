@if($event->timelineItems->isNotEmpty())
<section class="block event-timeline">
  <h2>Event timeline</h2>
  <div class="event-timeline-shell">
    <ol class="event-timeline-list">
      @foreach($event->timelineItems as $item)
        <li class="event-timeline-item">
          <div class="event-timeline-marker" aria-hidden="true">
            <span class="event-timeline-dot"></span>
          </div>
          <div class="event-timeline-card">
            @if(filled($item->time_label))
              <p class="event-timeline-date">{{ $item->time_label }}</p>
            @endif
            <p class="event-timeline-title">{{ $item->title }}</p>
          </div>
        </li>
      @endforeach
    </ol>
  </div>
</section>
@endif
