@extends($accountLayout)

@section('title')
Order tickets — {{ $siteName }}
@endsection

@section('meta_description')
View and download each ticket in your booking order.
@endsection

@section('content')
<div class="container account-shell">
  @include('public.partials.account-tabs')

  <div class="account-panel">
    <h1 class="account-heading">Order tickets</h1>
    <p class="account-intro">
      @if($event)
        {{ $event->title }}
      @else
        Booking order
      @endif
      · {{ $tickets->count() }} ticket{{ $tickets->count() > 1 ? 's' : '' }}
    </p>

    <p class="account-intro" style="margin-top:-8px">
      <a href="{{ route('account.index') }}" class="account-link">Back to My account</a>
    </p>

    <ul class="account-booking-list">
      @foreach($tickets as $ticketBooking)
        <li class="account-booking-row">
          <div>
            <strong>{{ $ticketBooking->ticket?->name ?? 'Ticket' }}</strong>
            @if($seatLabel = $ticketBooking->seatDisplayLabel())
              <div class="meta">Seat {{ $seatLabel }}</div>
            @endif
            @if($ticketBooking->attendee_name)
              <div class="meta">{{ $ticketBooking->attendee_name }}@if($ticketBooking->email) · {{ $ticketBooking->email }}@endif</div>
            @endif
            @if($ticketBooking->occurrence_date)
              <div class="meta">{{ $ticketBooking->occurrence_date->format('l, M j, Y') }}</div>
            @endif
            <div class="meta">Booking #{{ $ticketBooking->id }} · {{ $ticketBooking->created_at?->format('M j, Y g:i A') }}</div>
          </div>
          <div class="account-booking-actions">
            <span class="badge-status">{{ str_replace('_', ' ', (string) $ticketBooking->status) }}</span>
            <a href="{{ route('account.bookings.ticket-pdf', $ticketBooking) }}" class="account-link">Download ticket PDF</a>
          </div>
        </li>
      @endforeach
    </ul>
  </div>
</div>
@endsection

