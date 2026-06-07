<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 24px; }
    h1 { font-size: 20px; margin: 0 0 8px; }
    .ticket-header { margin-bottom: 20px; }
    h2 { font-size: 14px; margin: 20px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
    .muted { color: #555; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    td { padding: 6px 0; vertical-align: top; }
    td.label { width: 32%; font-weight: bold; color: #333; }
    .qr-wrap { text-align: center; margin-top: 24px; padding: 16px; border: 1px dashed #999; }
    .qr-wrap img { width: 200px; height: 200px; }
    .footer { margin-top: 28px; font-size: 10px; color: #666; }
  </style>
</head>
<body>
  @php
    $show = static fn (string $k): bool => (bool) ($pdfFields[$k] ?? true);
    $loc = trim($event->fullVenueAddressLine());
    if ($loc === '') {
      $loc = $event->locationLabel();
    }
  @endphp

  @include('partials.ticket-header', [
    'event' => $event,
    'pdfFields' => $pdfFields,
    'branding' => $branding,
    'forPdf' => $forPdf ?? true,
  ])

  @if($show('event_datetime'))
    <p class="muted">
      @if($booking->occurrence_date)
        <strong>Your session:</strong> {{ $booking->occurrence_date->format('l, F j, Y') }}
        @if($event->startsAtDisplay())
          <span class="muted"> · {{ $event->startsAtDisplay()->format('g:i A') }}@if($event->endsAtDisplay()) – {{ $event->endsAtDisplay()->format('g:i A') }}@endif</span>
        @endif
      @elseif($event->startsAtDisplay())
        {{ $event->startsAtDisplay()->format('l, F j, Y g:i A') }}
        @if($event->endsAtDisplay())
          – {{ $event->endsAtDisplay()->format('M j, Y g:i A') }}
        @endif
      @else
        Date TBD
      @endif
    </p>
  @endif

  @if($show('event_location') && $loc !== '')
    <p class="muted">{{ $loc }}</p>
  @endif

  @if($show('location_type'))
    <p class="muted">Location type: {{ ucfirst((string) ($event->location_type ?? 'n/a')) }}</p>
  @endif

  @if($show('attendee_name') || $show('attendee_email') || $show('attendee_phone'))
    <h2>Attendee</h2>
    <table>
      @if($show('attendee_name'))
        <tr><td class="label">Name</td><td>{{ $booking->attendee_name }}</td></tr>
      @endif
      @if($show('attendee_email') && $booking->email)
        <tr><td class="label">Email</td><td>{{ $booking->email }}</td></tr>
      @endif
      @if($show('attendee_phone') && $booking->phone)
        <tr><td class="label">Phone</td><td>{{ $booking->phone }}</td></tr>
      @endif
    </table>
  @endif

  @php
    $seatLabel = $booking->seatDisplayLabel();
  @endphp

  @if($show('booking_id') || $show('ticket_type') || $show('seat_number') || $show('session_date') || $show('tier_price') || $show('order_status') || $show('payment_reference') || $show('notes'))
    <h2>Ticket &amp; purchase</h2>
    <table>
      @if($show('booking_id'))
        <tr><td class="label">Confirmation #</td><td>{{ $booking->id }}</td></tr>
      @endif
      <tr><td class="label">Booked</td><td>{{ $booking->created_at?->format('M j, Y g:i A') }}</td></tr>
      @if($show('ticket_type'))
        <tr><td class="label">Ticket tier</td><td>{{ $booking->ticket?->name ?? '—' }}</td></tr>
      @endif
      @if($show('seat_number') && $seatLabel)
        <tr><td class="label">Seat</td><td>{{ $seatLabel }}</td></tr>
      @endif
      @if($show('session_date') && $booking->occurrence_date)
        <tr><td class="label">Session date</td><td>{{ $booking->occurrence_date->format('l, M j, Y') }}</td></tr>
      @endif
      @if($show('tier_price') && $unitPrice !== null)
        <tr><td class="label">Tier price</td><td>${{ number_format((float) $unitPrice, 2) }}</td></tr>
      @endif
      @if($show('order_status'))
        <tr><td class="label">Order status</td><td>{{ str_replace('_', ' ', $booking->status) }}</td></tr>
      @endif
      @if($show('payment_reference') && $paymentRef)
        <tr><td class="label">Payment reference</td><td>{{ $paymentRef }}</td></tr>
      @endif
      @if($show('notes') && $booking->notes)
        <tr><td class="label">Notes</td><td>{{ $booking->notes }}</td></tr>
      @endif
    </table>
  @endif

  @if($show('checkin_qr'))
    <div class="qr-wrap">
      <p style="margin:0 0 12px;font-weight:bold;">Check-in QR (staff only)</p>
      <img src="{{ $qrDataUri }}" alt="QR code" />
      <p class="muted" style="margin:12px 0 0;font-size:9px;word-break:break-all;">{{ $checkInUrl }}</p>
    </div>
  @endif

  <p class="footer">
    Present this ticket at the venue.
    @if($show('checkin_qr'))
      The QR code may only be scanned by signed-in organizers in the admin panel.
    @endif
  </p>
</body>
</html>
