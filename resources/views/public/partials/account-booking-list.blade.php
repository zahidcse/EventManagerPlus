<ul class="account-booking-list">
  @foreach($bookings as $booking)
    @php
      $ev = $booking->event;
      $starts = $ev?->startsAtDisplay();
      $orderUrl = \Illuminate\Support\Facades\Route::has('account.bookings.order')
        ? route('account.bookings.order', $booking->order_group_id)
        : null;
    @endphp
    <li class="account-booking-card">
      <div class="account-booking-main">
        <div class="account-booking-info">
          @if($ev)
            <a href="{{ route('events.show', $ev) }}" class="account-booking-title">{{ $ev->title }}</a>
          @else
            <span class="account-booking-title">Event</span>
          @endif
          @if($booking->occurrence_date)
            <div class="meta">
              {{ $booking->occurrence_date->format('l, M j, Y') }}{{ $starts ? ' · '.$starts->format('g:i A') : '' }}
            </div>
          @elseif($starts)
            <div class="meta">{{ $starts->format('M j, Y g:i A') }}</div>
          @endif
          <div class="meta">{{ $booking->ticket_summary }}</div>
        </div>
        <span class="badge-status">{{ str_replace('_', ' ', $booking->status_label) }}</span>
      </div>
      <div class="account-booking-footer">
        @if($orderUrl)
          <a href="{{ $orderUrl }}" class="account-btn">
            View order{{ $booking->tickets_count > 1 ? ' ('.$booking->tickets_count.' tickets)' : '' }}
          </a>
        @else
          <a href="{{ route('account.bookings.ticket-pdf', $booking->primary_booking) }}" class="account-btn">Download ticket</a>
        @endif
      </div>
    </li>
  @endforeach
</ul>
