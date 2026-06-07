<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ticket #{{ $booking->id }} — {{ $event->title }}</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; font-size: 14px; color: #111; margin: 24px; max-width: 640px; }
    h1 { font-size: 22px; margin: 0 0 8px; }
    .ticket-header { margin-bottom: 20px; }
    h2 { font-size: 15px; margin: 20px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
    .muted { color: #555; font-size: 13px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    td { padding: 6px 0; vertical-align: top; }
    td.label { width: 32%; font-weight: 600; color: #333; }
    .qr-wrap { text-align: center; margin-top: 24px; padding: 16px; border: 1px dashed #999; }
    .qr-wrap img { width: 200px; height: 200px; }
    .footer { margin-top: 28px; font-size: 12px; color: #666; }
    .no-print { margin-bottom: 20px; display: flex; gap: 12px; flex-wrap: wrap; }
    .no-print button, .no-print a {
      padding: 10px 16px; border-radius: 8px; border: 1px solid #ccc;
      background: #fff; cursor: pointer; text-decoration: none; color: #111; font-size: 14px;
    }
    .no-print button.primary { background: #7c3aed; border-color: #6d28d9; color: #fff; }
    @media print {
      .no-print { display: none !important; }
      body { margin: 12px; }
    }
  </style>
</head>
<body>
  <div class="no-print">
    <button type="button" class="primary" onclick="window.print()">Print ticket</button>
    <a href="javascript:window.close()">Close</a>
  </div>

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
    'forPdf' => $forPdf ?? false,
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
    $ticketName = $booking->ticket?->name ?? 'Ticket';
    $seatLabel = $booking->seatDisplayLabel();
  @endphp

  @if($show('ticket_type') || $show('seat') || $show('booking_id') || $show('price'))
    <h2>Ticket</h2>
    <table>
      @if($show('ticket_type'))
        <tr><td class="label">Type</td><td>{{ $ticketName }}</td></tr>
      @endif
      @if($show('seat') && $seatLabel)
        <tr><td class="label">Seat</td><td>{{ $seatLabel }}</td></tr>
      @endif
      @if($show('booking_id'))
        <tr><td class="label">Booking #</td><td>{{ $booking->id }}</td></tr>
      @endif
      @if($show('price') && $unitPrice !== null)
        <tr><td class="label">Price</td><td>{{ number_format((float) $unitPrice, 2) }}</td></tr>
      @endif
      @if($paymentRef && $show('payment_reference'))
        <tr><td class="label">Payment</td><td>{{ $paymentRef }}</td></tr>
      @endif
    </table>
  @endif

  @if($show('qr_code'))
    <div class="qr-wrap">
      <p class="muted" style="margin-top:0">Scan at check-in</p>
      <img src="{{ $qrDataUri }}" alt="Check-in QR code" width="200" height="200">
      <p class="muted" style="word-break:break-all;font-size:11px">{{ $checkInUrl }}</p>
    </div>
  @endif

  <p class="footer">Present this ticket (printed or on your phone) at the event entrance.</p>

  @if($autoPrint)
    <script>window.addEventListener('load', function () { window.print(); });</script>
  @endif
</body>
</html>
