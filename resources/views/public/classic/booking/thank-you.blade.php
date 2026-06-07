@extends($publicLayout)

@section('title')
Booking confirmed — {{ $siteName }}
@endsection

@section('meta_description')
Your booking is confirmed. View order details and print your tickets.
@endsection

@section('content')
<div class="container" style="max-width: 820px; margin: 32px auto 48px; padding: 0 16px;">
  <div class="account-panel">
    <h1 class="account-heading" style="margin-bottom: 8px;">Thank you!</h1>
    @if($message !== '')
      <p class="account-intro">{{ $message }}</p>
    @else
      <p class="account-intro">Your booking has been received.</p>
    @endif

    @if($accountReady)
      <p class="account-intro" style="margin-top: -4px;">
        <a href="{{ route('account.index') }}" class="account-link">Go to My account</a> to see all your bookings.
      </p>
    @endif

    <p class="account-intro" style="margin-top: 12px;">
      <strong>{{ $event->title }}</strong>
      · {{ $tickets->count() }} ticket{{ $tickets->count() > 1 ? 's' : '' }}
    </p>

    @foreach($orders as $order)
      @php
        $orderTickets = $order->tickets;
      @endphp
      @if($orders->count() > 1)
        <h2 style="font-size: 1.1rem; margin: 24px 0 8px;">Order {{ $loop->iteration }}</h2>
      @endif

      <ul class="account-booking-list">
        @foreach($orderTickets as $ticketBooking)
          <li class="account-booking-row">
            <div>
              <strong>{{ $ticketBooking->ticket?->name ?? 'Ticket' }}</strong>
              @if($seatLabel = $ticketBooking->seatDisplayLabel())
                <div class="meta">Seat {{ $seatLabel }}</div>
              @endif
              @if($ticketBooking->attendee_name)
                <div class="meta">
                  {{ $ticketBooking->attendee_name }}
                  @if($ticketBooking->email) · {{ $ticketBooking->email }}@endif
                </div>
              @endif
              @if($ticketBooking->occurrence_date)
                <div class="meta">{{ $ticketBooking->occurrence_date->format('l, M j, Y') }}</div>
              @endif
              <div class="meta">
                Booking #{{ $ticketBooking->id }}
                · {{ $ticketBooking->created_at?->format('M j, Y g:i A') }}
              </div>
            </div>
            <div class="account-booking-actions">
              <span class="badge-status">{{ str_replace('_', ' ', (string) $ticketBooking->status) }}</span>
              <a href="{{ route('events.booking.ticket-pdf', [$event, $ticketBooking]) }}" class="account-link">Download PDF</a>
              <a href="{{ route('events.booking.ticket-print', [$event, $ticketBooking, 'print' => 1]) }}" class="account-link" target="_blank" rel="noopener">Print ticket</a>
            </div>
          </li>
        @endforeach
      </ul>
    @endforeach

    <p style="margin-top: 24px; display: flex; flex-wrap: wrap; gap: 16px;">
      <a href="{{ route('events.show', $event) }}" class="account-link">Back to event</a>
      <a href="{{ route('events.index') }}" class="account-link">Browse more events</a>
    </p>
  </div>
</div>
@endsection
