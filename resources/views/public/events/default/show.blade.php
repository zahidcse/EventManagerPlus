@extends('public.layouts.frontend-default')

@section('title', $event->title.' - '.$siteName)
@section('meta_description')
{{ \Illuminate\Support\Str::limit(trim(strip_tags($event->description ?: ('Book tickets to '.$event->title))), 155) }}
@endsection

@push('head')
@if($event->cover_image_path)
<meta property="og:image" content="{{ asset('uploads/'.$event->cover_image_path) }}" />
@endif
<style>
  .attendee-ticket-block { margin-top: 0; padding-top: 0; border-top: 0; }
  .attendee-ticket-inline { flex: 0 0 100%; width: 100%; min-width: 100%; margin-top: 2px; padding-top: 16px; border-top: 1px solid rgba(148, 163, 184, .25); }
  .attendee-ticket-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 10px; width: 100%; }
  .attendee-ticket-grid.attendee-ticket-grid--inline { margin-top: 0; width: 100%; }
  .attendee-ticket-card { min-width: 0; border: 0; border-radius: 12px; padding: 14px; background: rgba(255, 255, 255, .045); box-shadow: 0 10px 28px rgba(15, 23, 42, .08); }
  .attendee-ticket-title { font-weight: 800; font-size: 14px; margin: 0 0 4px; }
  .attendee-ticket-subtitle { font-size: 12px; color: rgba(148, 163, 184, .95); margin: 0 0 12px; }
  .attendee-ticket-fields { display: grid; gap: 10px; }
  .attendee-ticket-fields label { display: grid; gap: 5px; min-width: 0; font-size: 12px; font-weight: 700; color: rgba(148, 163, 184, .95); }
  .attendee-ticket-fields input,
  .attendee-ticket-fields select { width: 100%; box-sizing: border-box; border: 1px solid rgba(148, 163, 184, .55); border-radius: 8px; background: transparent; color: inherit; padding: 9px 10px; font: inherit; outline: none; }
  .attendee-ticket-fields input:focus,
  .attendee-ticket-fields select:focus { border-color: #d946ef; box-shadow: 0 0 0 3px rgba(217, 70, 239, .18); }
@include('public.partials.attendee-custom-select-styles')
  @media (max-width: 760px) {
    .attendee-ticket-grid { grid-template-columns: 1fr; }
  }
  .seat-plan-stage {
    margin: 0 auto 24px; max-width: 70%; text-align: center; padding: 10px 12px;
    background: linear-gradient(180deg, rgba(30, 41, 59, .12), rgba(15, 23, 42, .04));
    border: 1px solid rgba(148, 163, 184, .35);
    border-top: 1px solid rgba(148, 163, 184, .35);
    border-radius: 15px; letter-spacing: .28em;
    font-weight: 700; font-size: 12px; color: #7c3aed;
  }
  .seat-plan-wrap { overflow-x: auto; padding: 8px 0 16px; }
  .seat-plan-grid { border-collapse: separate; border-spacing: 5px; margin: 0 auto; }
  .seat-plan-row-label { color: rgba(148, 163, 184, .9); font-size: 11px; padding: 0 6px; min-width: 20px; text-align: right; }
  .seat-plan-seat {
    width: 34px; height: 34px; border-radius: 6px 6px 9px 9px; border: 1px solid rgba(148, 163, 184, .45);
    background: rgba(30, 41, 59, .08); color: #1a1300; font-size: 10px; font-weight: 700; cursor: pointer; padding: 0;
  }
  .seat-plan-seat.has-ticket-color.is-available:not(.is-selected):not(:disabled) {
    background: var(--seat-ticket-color);
    border-color: var(--seat-ticket-color);
    color: #1a1300;
  }
  .seat-plan-seat.is-booked, .seat-plan-seat.is-reserved, .seat-plan-seat.is-blocked {
    opacity: .65; cursor: not-allowed; background: #94a3b8 !important; border-color: #64748b !important; color: #f8fafc !important;
  }
  .seat-plan-seat.is-booked { background: #b91c1c !important; border-color: #991b1b !important; }
  .seat-plan-seat.is-reserved { background: #a16207 !important; border-color: #854d0e !important; }
  .seat-plan-seat.is-selected {
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px #7c3aed;
  }
  .seat-plan-seat.has-ticket-color.is-selected.is-available {
    background: var(--seat-ticket-color);
    border-color: var(--seat-ticket-color);
    color: #1a1300 !important;
  }
  .seat-plan-seat:disabled { cursor: not-allowed; }
  .seat-plan-aisle, .seat-plan-empty { display: inline-block; width: 34px; height: 34px; }
  .seat-plan-legend {
    display: flex; flex-wrap: nowrap; gap: 10px 14px; justify-content: center; align-items: center;
    margin: 8px 0 18px; font-size: 12px; color: rgba(148, 163, 184, .95);
    overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch;
  }
  .seat-plan-legend-heading {
    flex: 0 0 auto; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    color: rgba(148, 163, 184, .8); white-space: nowrap;
  }
  .seat-plan-legend-item--status {
    border-left: 1px solid rgba(148, 163, 184, .35); padding-left: 12px; margin-left: 2px;
  }
  .seat-plan-legend-item { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; white-space: nowrap; }
  .seat-plan-legend-label { display: inline-flex; align-items: center; gap: 5px; line-height: 1.25; white-space: nowrap; }
  .seat-plan-legend-name { font-weight: 700; color: inherit; }
  .seat-plan-legend-price { font-size: 11px; color: rgba(148, 163, 184, .95); font-weight: 600; }
  .seat-plan-price-was { text-decoration: line-through; margin-right: 4px; opacity: .85; font-weight: 500; }
  .seat-plan-swatch { display: inline-block; width: 14px; height: 14px; border-radius: 3px; flex-shrink: 0; border: 1px solid rgba(148, 163, 184, .4); }
  .seat-plan-swatch.is-selected { background: #7c3aed; border-color: #6d28d9; box-shadow: 0 0 0 1px #fff, 0 0 0 2px #7c3aed; }
  .seat-plan-swatch.is-booked { background: #b91c1c; border-color: #991b1b; }
  .seat-plan-swatch.is-reserved { background: #a16207; border-color: #854d0e; }
  .seat-plan-swatch.is-blocked { background: #64748b; border-color: #475569; }
  .seat-plan-addons-heading { margin: 22px 0 8px; font-size: 16px; font-weight: 700; }
  .seat-plan-addon-options { margin-top: 0; }
  .seat-plan-attendees-section { margin-top: 22px; }
  .seat-plan-attendees-heading { margin: 0 0 12px; font-size: 16px; font-weight: 700; }
  .seat-plan-attendees {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 0;
  }
  .seat-plan-attendee-card { border: 1px solid rgba(148, 163, 184, .35); border-radius: 12px; padding: 14px; background: rgba(255, 255, 255, .04); }
  .seat-plan-attendee-card .attendee-ticket-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }
  @media (max-width: 760px) {
    .seat-plan-attendees { grid-template-columns: 1fr; }
    .seat-plan-attendee-card .attendee-ticket-fields { grid-template-columns: 1fr; }
  }
  .seat-plan-modal { position: fixed; inset: 0; z-index: 1200; display: flex; align-items: center; justify-content: center; padding: 16px; }
  .seat-plan-modal[hidden] { display: none !important; }
  .seat-plan-modal-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .55); }
  .seat-plan-modal-panel {
    position: relative; z-index: 1; width: min(100%, 420px); border-radius: 12px; padding: 20px;
    background: #fff; color: #0f172a; border: 1px solid rgba(148, 163, 184, .4);
    box-shadow: 0 20px 50px rgba(15, 23, 42, .25);
  }
  .seat-plan-modal-title { margin: 0 0 8px; font-size: 18px; font-weight: 700; }
  .seat-plan-modal-text { margin: 0 0 16px; font-size: 14px; color: #475569; line-height: 1.45; }
  .seat-plan-modal-actions { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; }
  .seat-plan-modal-btn {
    border-radius: 8px; padding: 9px 14px; font-size: 14px; font-weight: 600; cursor: pointer; border: 1px solid transparent;
  }
  .seat-plan-modal-btn--cancel { background: transparent; border-color: rgba(148, 163, 184, .55); color: inherit; }
  .seat-plan-modal-btn--confirm { background: #7c3aed; border-color: #6d28d9; color: #fff; }
</style>
@endpush

@php
  $priceFromJs = $priceFrom !== null ? (float) $priceFrom : 0;
@endphp

@section('content')
  @if(session('booked'))
    <div class="alert-success" role="status">
      {{ session('booked') }}
      @if(session('booked_account_ready'))
        <span class="booked-account-link-wrap"> Go to <a href="{{ route('account.index') }}">My account</a> for your bookings.</span>
      @endif
    </div>
  @endif

  @if(session('book_error'))
    <div class="alert-error" role="alert">{{ session('book_error') }}</div>
  @endif
  @if(request('payment') === 'cancelled')
    <div class="alert-error" role="alert">Payment was cancelled.</div>
  @endif

  @if($errors->any())
    <div class="alert-error" role="alert">
      <strong>Please fix the following:</strong>
      <ul style="margin:8px 0 0 18px;padding:0">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
      </ul>
    </div>
  @endif

  @php
    $hero = $galleryUrls->first() ?: 'https://images.unsplash.com/photo-1506157786151-b8491531f063?w=1600&q=80';
    $priceFromDisp = $priceFrom !== null ? ($priceFrom == floor($priceFrom) ? number_format($priceFrom, 0) : number_format($priceFrom, 2)) : '0';
    $sidebarWhen = $event->sidebarWhenLines();
    $mapUrl = $event->mapEmbedUrl();
    $addrOrLoc = $event->fullVenueAddressLine() !== '' ? $event->fullVenueAddressLine() : $event->locationLabel();
    $pmStripe = !empty($showStripePayments);
    $pmPayPal = !empty($showPayPalPayments);
    $pmRazorpay = !empty($showRazorpayPayments);
    $pmSslcommerz = !empty($showSslCommerzPayments);
  @endphp

  @include('public.partials.event-detail-hero', [
    'event' => $event,
    'galleryUrls' => $galleryUrls,
    'hero' => $hero,
    'sidebarWhen' => $sidebarWhen,
    'addrOrLoc' => $addrOrLoc,
  ])
  <form method="post" action="{{ route('events.book', $event) }}" id="booking-form">
  @csrf
  <div class="layout layout--event-detail">
    @include('public.partials.event-detail-about', ['event' => $event, 'variantClass' => 'layout-about--mobile'])

    <div class="layout-main">
      @include('public.partials.event-detail-about', ['event' => $event, 'variantClass' => 'layout-about--desktop'])

      <div class="layout-event-timeline">
        @include('public.partials.event-detail-timeline', ['event' => $event])
      </div>

      <div class="layout-booking-main">
      @include('public.partials.event-booking-session-date')

      @php
        $usesPerDayCart = ($event->schedule_type ?? 'single') !== 'single' && count($event->bookableOccurrenceDateStrings()) > 0;
        $useSeatPlanBooking = !empty($useSeatPlanBooking) && !empty($seatPlanViewData);
      @endphp
      @if($useSeatPlanBooking)
        @php
          $seatPlanMultiDay = ($event->schedule_type ?? 'single') !== 'single' && count($event->bookableOccurrenceDateStrings()) > 0;
          $seatPlanActiveDate = old('occurrence_date', $event->bookableOccurrenceDateStrings()[0] ?? '');
          $seatPlanActiveLabel = $seatPlanActiveDate !== '' ? \Illuminate\Support\Carbon::parse($seatPlanActiveDate)->format('D, M j, Y') : '';
        @endphp
        <div
          class="booking-cart-root booking-cart--seat-plan {{ $seatPlanMultiDay ? 'booking-cart--seat-plan-multi' : '' }}"
          id="booking-cart-seat-plan"
          @if($seatPlanMultiDay) data-active-session-date="{{ $seatPlanActiveDate }}" data-active-session-label="{{ $seatPlanActiveLabel }}" @endif
        >
          @include('public.partials.event-booking-seat-plan', ['event' => $event, 'seatPlanViewData' => $seatPlanViewData, 'attendeeSettings' => $attendeeSettings])
        </div>
      @else
      <div class="booking-cart-root {{ $usesPerDayCart ? 'booking-cart--multi' : 'booking-cart--single' }}">
        @if($usesPerDayCart)
          @php
            $bookableDays = $event->bookableOccurrenceDateStrings();
          @endphp
          <div class="session-date-tabs booking-day-tabs" role="tablist" aria-label="Choose event day">
            @foreach($bookableDays as $i => $d)
              @php $dayCarbon = \Illuminate\Support\Carbon::parse($d); @endphp
              <button
                type="button"
                class="session-date-tab booking-day-tab {{ $i === 0 ? 'is-active' : '' }}"
                role="tab"
                id="booking-day-tab-{{ $loop->index }}"
                aria-controls="booking-day-panel-{{ $loop->index }}"
                aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                tabindex="{{ $i === 0 ? '0' : '-1' }}"
                data-day-target="booking-day-panel-{{ $loop->index }}"
                data-session-date="{{ $d }}"
              >
                <span class="session-date-tab-inner">{{ $dayCarbon->format('D, M j') }}</span>
              </button>
            @endforeach
          </div>
          @foreach($bookableDays as $i => $d)
            @php $dayCarbon = \Illuminate\Support\Carbon::parse($d); @endphp
            <section
              class="block per-session-cart booking-day-panel {{ $i === 0 ? 'is-active' : '' }}"
              id="booking-day-panel-{{ $loop->index }}"
              role="tabpanel"
              aria-labelledby="booking-day-tab-{{ $loop->index }}"
              @if($i !== 0) hidden @endif
              data-session-date="{{ $d }}"
              data-session-label="{{ $dayCarbon->format('D, M j, Y') }}"
            >
              <h2>{{ $dayCarbon->format('l, M j, Y') }}</h2>
              <p class="body-text ep-per-day-hint" style="margin-top:0">Tickets and add-ons below apply to this day only.</p>
              <h3 class="ep-subhead-tickets" style="margin:12px 0 8px;font-size:16px;font-weight:700">Tickets</h3>
              @include('public.partials.event-booking-ticket-options', ['event' => $event, 'perDayDate' => $d])
            </section>
          @endforeach
        @else
          <section class="block">
            <h2>Choose your tickets</h2>
            @if($event->additionalServices->isNotEmpty())
              <p class="body-text ep-event-addons-intro">Optional extras appear below the ticket tiers.</p>
            @endif
            @include('public.partials.event-booking-ticket-options', ['event' => $event, 'perDayDate' => null])
          </section>
        @endif
      </div>
      @endif
      </div>

      <div class="layout-event-details">
      @if($event->organizer)
      <section class="block">
        <h2>Organizer</h2>
        <div class="organizer">
          @php
            $orgImg = $event->organizer->photo_path ? asset('uploads/'.$event->organizer->photo_path) : 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=400&q=80';
          @endphp
          <img src="{{ $orgImg }}" alt="{{ $event->organizer->name }}" />
          <div>
            <div class="name">{{ $event->organizer->name }}</div>
            <div class="desc">{{ $event->organizer->bio ?: ($event->organizer->company_name ?: '') }}</div>
          </div>
        </div>
      </section>
      @endif

      @if($event->speakers->isNotEmpty())
      <section class="block">
        <h2>Speakers &amp; performers</h2>
        <div class="speakers">
          @foreach($event->speakers as $speaker)
            <div class="speaker-card">
              <img src="{{ $speaker->photoUrl() ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&q=80' }}" alt="{{ $speaker->name }}" />
              <div class="name">{{ $speaker->name }}</div>
              <div class="role">{{ $speaker->headline ?: 'Speaker' }}</div>
            </div>
          @endforeach
        </div>
      </section>
      @endif

      @include('public.partials.event-detail-gallery', ['galleryUrls' => $galleryUrls])

      @if($event->faqs->isNotEmpty())
      <section class="block faq">
        <h2>Frequently asked questions</h2>
        @foreach($event->faqs as $faq)
          <details><summary>{{ $faq->question }}</summary><div class="body-text">{!! \App\Support\RichTextSanitizer::html($faq->answer) !!}</div></details>
        @endforeach
      </section>
      @endif

      <section class="block">
        <h2>Location</h2>
        @if($mapUrl)
          <p class="body-text" style="font-size:14px;">{{ $event->fullVenueAddressLine() }}</p>
          <div class="map-frame">
            <iframe src="{{ $mapUrl }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map"></iframe>
          </div>
        @else
          <p class="body-text" style="font-size:14px;">{{ $addrOrLoc }}</p>
          @if($event->location_type === 'virtual' && $event->meeting_url)
            <p class="body-text"><a href="{{ $event->meeting_url }}" target="_blank" rel="noopener">Online access link</a></p>
          @endif
        @endif
      </section>
      </div>
    </div>

    <aside class="sidebar">
      @include('public.partials.event-booking-sidebar-event-info', [
        'event' => $event,
        'sidebarWhen' => $sidebarWhen,
        'addrOrLoc' => $addrOrLoc,
      ])

      <div>
        <div class="label" id="orderLabel">Tickets from</div>
        <div class="price-big" id="totalPrice">${{ $priceFromDisp }}</div>
        <div id="itemsSummary" class="order-items-summary" style="display:none">
          <div class="label" style="margin-top:12px">Selected items</div>
          <ul id="itemsList" class="order-items-list"></ul>
        </div>
      </div>

      @include('public.partials.event-booking-payment-sidebar')

      @include('public.partials.booking-auth-notice')

      <div class="book-fields">
        <label for="attendee_name">Your name</label>
        <input id="attendee_name" name="attendee_name" required value="{{ $bookingDefaults['attendee_name'] }}" autocomplete="name" />

        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="{{ $bookingDefaults['email'] }}" autocomplete="email" />

      @include('public.partials.booking-guest-signup')

        <label for="phone">Phone (optional)</label>
        <input id="phone" name="phone" value="{{ $bookingDefaults['phone'] }}" autocomplete="tel" />

      </div>

      <button type="submit" class="btn" id="bookBtn" disabled>Select tickets to book</button>
    </aside>
  </div>
  </form>
@endsection

@push('scripts')
<script>
@include('public.partials.attendee-custom-select-script')
const PRICE_FROM = {{ json_encode($priceFromJs) }};
const PAYMENT_STRIPE = @json($pmStripe);
const PAYMENT_PAYPAL = @json($pmPayPal);
const PAYMENT_RAZORPAY = @json($pmRazorpay);
const PAYMENT_SSLCOMMERZ = @json($pmSslcommerz);
const PAYMENT_OFFLINE_CASH = @json(!empty($showCashOfflinePayments));
const PAYMENT_OFFLINE_BANK = @json(!empty($showBankOfflinePayments));
const ATTENDEE_SETTINGS = @json($attendeeSettings ?? \App\Models\Event::defaultAttendeeSettings());
const ATTENDEE_FIELD_DEFINITIONS = @json($attendeeFieldDefinitions ?? \App\Models\Event::attendeeFieldDefinitions());
const ATTENDEE_OLD_ENTRIES = @json(old('attendee_entries', []));
const USE_SEAT_PLAN = @json(!empty($useSeatPlanBooking) && !empty($seatPlanViewData));
const SEAT_PLAN_MULTI_DAY = @json(($seatPlanViewData ?? [])['uses_per_day_seat_inventory'] ?? false);
const SEAT_PLAN_BOOKED_BY_DATE = @json(($seatPlanViewData ?? [])['booked_seats_by_date'] ?? []);
const OLD_SEAT_IDS = @json(collect(old('seat_ids', []))->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values()->all());

function getSidebarPaymentMethod() {
  var r = document.querySelector('aside input[name="payment_method"]:checked');
  if (r) return String(r.value);
  var h = document.querySelector('aside input[type="hidden"][name="payment_method"]');
  return h ? String(h.value) : '';
}

function syncOfflinePaymentFields() {
  var box = document.getElementById('offlinePaymentFields');
  if (!box) return;
  var instr = document.getElementById('bankTransferInstructions');
  var pm = getSidebarPaymentMethod();
  var offline = pm === 'cash' || pm === 'bank_transfer';
  box.style.display = offline ? 'block' : 'none';
  if (instr) {
    var showInstr = offline && PAYMENT_OFFLINE_BANK && pm === 'bank_transfer';
    instr.style.display = showInstr ? 'block' : 'none';
  }
}

function moneyFmt(n) {
  var r = Math.round(n * 100) / 100;
  return r % 1 === 0 ? r.toFixed(0) : r.toFixed(2);
}

function attendeeFieldConfigByKey(fieldKey) {
  return (ATTENDEE_FIELD_DEFINITIONS && ATTENDEE_FIELD_DEFINITIONS[fieldKey]) ? ATTENDEE_FIELD_DEFINITIONS[fieldKey] : null;
}

function attendeeEnabledFieldKeys() {
  if (!ATTENDEE_SETTINGS || !ATTENDEE_SETTINGS.enabled || !ATTENDEE_SETTINGS.fields) return [];
  var out = [];
  Object.keys(ATTENDEE_SETTINGS.fields).forEach(function (k) {
    if (ATTENDEE_SETTINGS.fields[k]) out.push(k);
  });
  return out;
}

function collectCurrentAttendeeEntries() {
  var out = [];
  document.querySelectorAll('input[name^="attendee_entries["], select[name^="attendee_entries["]').forEach(function (el) {
    var match = String(el.name || '').match(/^attendee_entries\[(\d+)\]\[([a-z_]+)\]$/);
    if (!match) return;
    var idx = parseInt(match[1], 10);
    var key = match[2];
    if (!out[idx]) out[idx] = {};
    out[idx][key] = String(el.value || '');
  });
  return out;
}

function syncAttendeePerTicketFields(ticketAssignments) {
  var currentRows = collectCurrentAttendeeEntries();
  var fallbackRows = Array.isArray(ATTENDEE_OLD_ENTRIES) ? ATTENDEE_OLD_ENTRIES : [];

  document.querySelectorAll('.attendee-ticket-inline[data-attendee-inline]').forEach(function (mount) {
    mount.style.display = 'none';
    var grid = mount.querySelector('.attendee-ticket-grid');
    if (grid) grid.innerHTML = '';
  });

  var enabledFields = attendeeEnabledFieldKeys();
  if (!ATTENDEE_SETTINGS || !ATTENDEE_SETTINGS.enabled || enabledFields.length === 0 || !ticketAssignments.length) {
    return;
  }

  for (var i = 0; i < ticketAssignments.length; i++) {
    var seed = (currentRows[i] && typeof currentRows[i] === 'object') ? currentRows[i] : ((fallbackRows[i] && typeof fallbackRows[i] === 'object') ? fallbackRows[i] : {});
    var assignment = ticketAssignments[i] || {};
    var optionEl = assignment.optionEl || null;
    if (!optionEl) continue;
    var mount = optionEl.querySelector('.attendee-ticket-inline[data-attendee-inline]');
    if (!mount) continue;
    var rowsWrap = mount.querySelector('.attendee-ticket-grid');
    if (!rowsWrap) continue;
    mount.style.display = 'block';

    var card = document.createElement('div');
    card.className = 'attendee-ticket-card';

    var title = document.createElement('p');
    title.className = 'attendee-ticket-title';
    title.textContent = 'Attendee #' + (i + 1);
    card.appendChild(title);

    var subtitle = document.createElement('p');
    subtitle.className = 'attendee-ticket-subtitle';
    var subtitleParts = [];
    if (assignment.dayLabel) subtitleParts.push(assignment.dayLabel);
    if (assignment.ticketName) subtitleParts.push(assignment.ticketName);
    subtitle.textContent = subtitleParts.length ? subtitleParts.join(' - ') : 'Ticket seat details';
    card.appendChild(subtitle);

    var fieldWrap = document.createElement('div');
    fieldWrap.className = 'attendee-ticket-fields';

    enabledFields.forEach(function (fieldKey) {
      var cfg = attendeeFieldConfigByKey(fieldKey);
      if (!cfg) return;

      var label = document.createElement('label');
      label.textContent = cfg.label;

      if (cfg.type === 'select') {
        var selectedValue = seed[fieldKey] ? String(seed[fieldKey]) : '';
        label.appendChild(createAttendeeSelectField(cfg, 'attendee_entries[' + i + '][' + fieldKey + ']', selectedValue, true));
      } else {
        var input = document.createElement('input');
        input.type = cfg.type;
        input.name = 'attendee_entries[' + i + '][' + fieldKey + ']';
        input.value = seed[fieldKey] ? String(seed[fieldKey]) : '';
        input.required = true;
        if (cfg.autocomplete) input.autocomplete = cfg.autocomplete;
        label.appendChild(input);
      }

      fieldWrap.appendChild(label);
    });

    card.appendChild(fieldWrap);
    rowsWrap.appendChild(card);
  }
}

function getSessionDayCount() {
  var root = document.querySelector('.booking-cart-root.booking-cart--multi');
  if (!root) return 1;
  var panels = root.querySelectorAll('.booking-day-panel');
  return panels.length || 1;
}

function getSelectedSessionLabels() {
  var root = document.querySelector('.booking-cart-root.booking-cart--multi');
  if (!root) return [];
  var labels = [];
  root.querySelectorAll('.booking-day-panel').forEach(function (panel) {
    var hasQty = false;
    panel.querySelectorAll('.count').forEach(function (countEl) {
      if ((parseInt(countEl.textContent, 10) || 0) > 0) hasQty = true;
    });
    if (hasQty) {
      labels.push(panel.getAttribute('data-session-label') || '');
    }
  });
  return labels.filter(Boolean);
}

function initBookingDayTabs() {
  var root = document.querySelector('.booking-cart-root.booking-cart--multi');
  if (!root) return;

  var tabs = Array.prototype.slice.call(root.querySelectorAll('.booking-day-tab[data-day-target]'));
  if (!tabs.length) return;

  function activateTab(targetId, focusTab) {
    tabs.forEach(function (tab) {
      var isActive = tab.getAttribute('data-day-target') === targetId;
      var panelId = tab.getAttribute('data-day-target');
      var panel = root.querySelector('#' + panelId);
      tab.classList.toggle('is-active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.setAttribute('tabindex', isActive ? '0' : '-1');
      if (panel) {
        panel.classList.toggle('is-active', isActive);
        if (isActive) {
          panel.removeAttribute('hidden');
        } else {
          panel.setAttribute('hidden', 'hidden');
        }
      }
      if (isActive && focusTab) {
        tab.focus();
      }
    });
    recalc();
  }

  tabs.forEach(function (tab, idx) {
    tab.addEventListener('click', function () {
      activateTab(tab.getAttribute('data-day-target'), false);
    });
    tab.addEventListener('keydown', function (e) {
      if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(e.key)) return;
      e.preventDefault();
      var nextIdx = idx;
      if (e.key === 'ArrowRight') nextIdx = (idx + 1) % tabs.length;
      if (e.key === 'ArrowLeft') nextIdx = (idx - 1 + tabs.length) % tabs.length;
      if (e.key === 'Home') nextIdx = 0;
      if (e.key === 'End') nextIdx = tabs.length - 1;
      var nextTab = tabs[nextIdx];
      if (!nextTab) return;
      activateTab(nextTab.getAttribute('data-day-target'), true);
    });
  });

  var initial = tabs.find(function (tab) { return tab.classList.contains('is-active'); }) || tabs[0];
  if (initial) {
    activateTab(initial.getAttribute('data-day-target'), false);
  }
}

function setHero(btn) {
  var img = document.getElementById('heroImg');
  if (!img || !btn.dataset.src) return;
  img.src = btn.dataset.src;
  document.querySelectorAll('.gallery button').forEach(function(b) { b.classList.remove('active'); });
  btn.classList.add('active');
}

function changeQty(btn, delta) {
  var wrap = btn.closest('.qty');
  if (!wrap) return;
  var hidden = wrap.querySelector('.qty-hidden');
  var countEl = wrap.querySelector('.count');
  var minusBtn = wrap.querySelector('button.minus') || wrap.querySelector('button:not(.plus)');
  var plusBtn = wrap.querySelector('button.plus');
  var max = hidden && hidden.dataset.max ? parseInt(hidden.dataset.max, 10) : 100;
  var n = Math.max(0, parseInt(countEl.textContent, 10) + delta);
  if (n > max) n = max;
  countEl.textContent = n;
  if (hidden) hidden.value = n;
  if (minusBtn) minusBtn.disabled = n === 0;
  if (plusBtn) plusBtn.disabled = n >= max;
  recalc();
}

var seatPlanSelected = new Map();

function effectiveSeatPlanStatus(baseStatus, seatId, occurrenceDate) {
  if (baseStatus === 'blocked' || baseStatus === 'reserved') return baseStatus;
  if (typeof SEAT_PLAN_MULTI_DAY === 'undefined' || !SEAT_PLAN_MULTI_DAY) return baseStatus;
  var booked = (typeof SEAT_PLAN_BOOKED_BY_DATE !== 'undefined' && SEAT_PLAN_BOOKED_BY_DATE)
    ? (SEAT_PLAN_BOOKED_BY_DATE[occurrenceDate] || [])
    : [];
  return booked.indexOf(seatId) !== -1 ? 'booked' : 'available';
}

function applySeatPlanButtonStatus(btn, status, isSelected) {
  btn.classList.remove('is-available', 'is-booked', 'is-reserved', 'is-blocked');
  btn.classList.add('is-' + status);
  btn.setAttribute('data-seat-status', status);
  if (status === 'available' || isSelected) {
    btn.disabled = false;
    btn.removeAttribute('aria-disabled');
  } else {
    btn.disabled = true;
    btn.setAttribute('aria-disabled', 'true');
  }
}

function refreshSeatPlanForOccurrenceDate(occurrenceDate) {
  if (typeof SEAT_PLAN_MULTI_DAY === 'undefined' || !SEAT_PLAN_MULTI_DAY || !occurrenceDate) return;
  document.querySelectorAll('.seat-plan-seat[data-seat-id]').forEach(function (btn) {
    var seatId = parseInt(btn.getAttribute('data-seat-id'), 10);
    var base = btn.getAttribute('data-base-status') || 'available';
    var isSelected = seatPlanSelected.has(seatId);
    applySeatPlanButtonStatus(btn, effectiveSeatPlanStatus(base, seatId, occurrenceDate), isSelected);
  });
}

function syncSeatIdsInputs() {
  var box = document.getElementById('seat-ids-inputs');
  if (!box) return;
  box.innerHTML = '';
  seatPlanSelected.forEach(function (meta, id) {
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'seat_ids[]';
    input.value = String(id);
    box.appendChild(input);
  });
}

function syncAttendeePerSeatFields() {
  var mount = document.getElementById('seat-plan-attendees');
  var section = document.getElementById('seat-plan-attendees-section');
  if (!mount) return;
  var currentRows = collectCurrentAttendeeEntries();
  var fallbackRows = Array.isArray(ATTENDEE_OLD_ENTRIES) ? ATTENDEE_OLD_ENTRIES : [];
  mount.innerHTML = '';
  var enabledFields = attendeeEnabledFieldKeys();
  if (!ATTENDEE_SETTINGS || !ATTENDEE_SETTINGS.enabled || enabledFields.length === 0 || seatPlanSelected.size === 0) {
    if (section) section.hidden = true;
    return;
  }
  if (section) section.hidden = false;
  var index = 0;
  var sorted = Array.from(seatPlanSelected.values()).sort(function (a, b) {
    return String(a.label).localeCompare(String(b.label));
  });
  sorted.forEach(function (meta) {
    var seed = (currentRows[index] && typeof currentRows[index] === 'object') ? currentRows[index] : ((fallbackRows[index] && typeof fallbackRows[index] === 'object') ? fallbackRows[index] : {});
    var card = document.createElement('div');
    card.className = 'seat-plan-attendee-card attendee-ticket-card';
    var title = document.createElement('p');
    title.className = 'attendee-ticket-title';
    title.textContent = 'Seat ' + meta.label;
    card.appendChild(title);
    var subtitle = document.createElement('p');
    subtitle.className = 'attendee-ticket-subtitle';
    subtitle.textContent = meta.ticketName ? (meta.ticketName + ' · $' + moneyFmt(meta.price)) : ('$' + moneyFmt(meta.price));
    card.appendChild(subtitle);
    var fieldWrap = document.createElement('div');
    fieldWrap.className = 'attendee-ticket-fields';
    enabledFields.forEach(function (fieldKey) {
      var cfg = attendeeFieldConfigByKey(fieldKey);
      if (!cfg) return;
      var label = document.createElement('label');
      label.textContent = cfg.label;
      if (cfg.type === 'select') {
        var selectedValue = seed[fieldKey] ? String(seed[fieldKey]) : '';
        label.appendChild(createAttendeeSelectField(cfg, 'attendee_entries[' + index + '][' + fieldKey + ']', selectedValue, true));
      } else {
        var input = document.createElement('input');
        input.type = cfg.type;
        input.name = 'attendee_entries[' + index + '][' + fieldKey + ']';
        input.value = seed[fieldKey] ? String(seed[fieldKey]) : '';
        input.required = true;
        if (cfg.autocomplete) input.autocomplete = cfg.autocomplete;
        label.appendChild(input);
      }
      fieldWrap.appendChild(label);
    });
    card.appendChild(fieldWrap);
    mount.appendChild(card);
    index++;
  });
}

function toggleSeatPlanSeat(btn) {
  if (!btn || btn.disabled) return;
  var id = parseInt(btn.getAttribute('data-seat-id'), 10);
  if (!id) return;
  var label = btn.getAttribute('data-seat-label') || ('#' + id);
  var ticketName = btn.getAttribute('data-ticket-name') || '';
  var price = parseFloat(btn.getAttribute('data-price') || '0') || 0;
  if (seatPlanSelected.has(id)) {
    seatPlanSelected.delete(id);
    btn.classList.remove('is-selected');
    btn.setAttribute('aria-pressed', 'false');
  } else {
    seatPlanSelected.set(id, { id: id, label: label, ticketName: ticketName, price: price });
    btn.classList.add('is-selected');
    btn.setAttribute('aria-pressed', 'true');
  }
  syncSeatIdsInputs();
  recalc();
}

function clearSeatPlanSelection() {
  seatPlanSelected.forEach(function (meta, id) {
    var btn = document.querySelector('.seat-plan-seat[data-seat-id="' + id + '"]');
    if (btn) {
      btn.classList.remove('is-selected');
      btn.setAttribute('aria-pressed', 'false');
    }
  });
  seatPlanSelected.clear();
  syncSeatIdsInputs();
  var mount = document.getElementById('seat-plan-attendees');
  var section = document.getElementById('seat-plan-attendees-section');
  if (mount) mount.innerHTML = '';
  if (section) section.hidden = true;
}

var seatPlanPendingDayTab = null;

function openSeatPlanDateChangeModal() {
  var modal = document.getElementById('seat-plan-date-change-modal');
  if (!modal) return;
  modal.hidden = false;
  document.body.style.overflow = 'hidden';
  var confirmBtn = modal.querySelector('[data-seat-plan-modal-confirm]');
  if (confirmBtn) confirmBtn.focus();
}

function closeSeatPlanDateChangeModal() {
  var modal = document.getElementById('seat-plan-date-change-modal');
  if (!modal) return;
  modal.hidden = true;
  document.body.style.overflow = '';
  seatPlanPendingDayTab = null;
}

function initSeatPlanDateChangeModal(applyDayFn) {
  var modal = document.getElementById('seat-plan-date-change-modal');
  if (!modal) return;

  modal.querySelectorAll('[data-seat-plan-modal-cancel]').forEach(function (el) {
    el.addEventListener('click', function () {
      closeSeatPlanDateChangeModal();
    });
  });

  var confirmBtn = modal.querySelector('[data-seat-plan-modal-confirm]');
  if (confirmBtn) {
    confirmBtn.addEventListener('click', function () {
      if (seatPlanPendingDayTab && seatPlanPendingDayTab.tab) {
        applyDayFn(seatPlanPendingDayTab.tab, seatPlanPendingDayTab.focusTab);
      }
      closeSeatPlanDateChangeModal();
    });
  }

  modal.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      e.preventDefault();
      closeSeatPlanDateChangeModal();
    }
  });
}

function initSeatPlanDayTabs() {
  var occInput = document.getElementById('seat-plan-occurrence-date');
  var root = document.getElementById('booking-cart-seat-plan');
  if (!occInput) return;

  var tabs = Array.prototype.slice.call(document.querySelectorAll('.seat-plan-day-tab[data-occurrence-date]'));
  if (!tabs.length) return;

  function applyDay(tab, focusTab) {
    var d = tab.getAttribute('data-occurrence-date');
    if (!d) return;
    if (occInput.value !== d) {
      clearSeatPlanSelection();
    }
    occInput.value = d;
    var label = tab.getAttribute('data-session-label') || tab.textContent.trim();
    tabs.forEach(function (t) {
      var active = t === tab;
      t.classList.toggle('is-active', active);
      t.setAttribute('aria-selected', active ? 'true' : 'false');
      t.setAttribute('tabindex', active ? '0' : '-1');
    });
    if (root) {
      root.setAttribute('data-active-session-date', d);
      root.setAttribute('data-active-session-label', label);
    }
    refreshSeatPlanForOccurrenceDate(d);
    if (focusTab) tab.focus();
    recalc();
  }

  function requestDay(tab, focusTab) {
    var d = tab.getAttribute('data-occurrence-date');
    if (!d || occInput.value === d) return;
    if (seatPlanSelected.size > 0) {
      seatPlanPendingDayTab = { tab: tab, focusTab: focusTab };
      openSeatPlanDateChangeModal();
      return;
    }
    applyDay(tab, focusTab);
  }

  initSeatPlanDateChangeModal(applyDay);

  tabs.forEach(function (tab, idx) {
    tab.addEventListener('click', function () {
      requestDay(tab, false);
    });
    tab.addEventListener('keydown', function (e) {
      if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(e.key)) return;
      e.preventDefault();
      var nextIdx = idx;
      if (e.key === 'ArrowRight') nextIdx = (idx + 1) % tabs.length;
      if (e.key === 'ArrowLeft') nextIdx = (idx - 1 + tabs.length) % tabs.length;
      if (e.key === 'Home') nextIdx = 0;
      if (e.key === 'End') nextIdx = tabs.length - 1;
      var nextTab = tabs[nextIdx];
      if (nextTab) requestDay(nextTab, true);
    });
  });

  var initial = tabs.find(function (tab) { return tab.classList.contains('is-active'); }) || tabs[0];
  if (initial) applyDay(initial, false);
}

function initSeatPlanBooking() {
  if (!USE_SEAT_PLAN) return;
  initSeatPlanDayTabs();
  document.querySelectorAll('.seat-plan-seat[data-seat-id]').forEach(function (btn) {
    btn.addEventListener('click', function () { toggleSeatPlanSeat(btn); });
  });
  if (Array.isArray(OLD_SEAT_IDS)) {
    OLD_SEAT_IDS.forEach(function (sid) {
      var btn = document.querySelector('.seat-plan-seat[data-seat-id="' + sid + '"]');
      if (!btn || btn.disabled) return;
      if (!seatPlanSelected.has(sid)) {
        toggleSeatPlanSeat(btn);
      }
    });
  }
  syncSeatIdsInputs();
}

function recalc() {
  var total = 0, itemCount = 0;
  var listRows = [];
  var ticketAssignments = [];
  var root = document.querySelector('.booking-cart-root');
  if (!root) return;

  if (USE_SEAT_PLAN && root.classList.contains('booking-cart--seat-plan')) {
    var sessionPrefix = root.getAttribute('data-active-session-label') || '';
    if (sessionPrefix) {
      listRows.push({ line: 'Session: ' + sessionPrefix });
    }
    seatPlanSelected.forEach(function (meta) {
      total += meta.price;
      itemCount += 1;
      var priceBit = meta.price > 0 ? ' · $' + moneyFmt(meta.price) : '';
      listRows.push({ line: 'Seat ' + meta.label + (meta.ticketName ? ' (' + meta.ticketName + priceBit + ')' : priceBit) });
    });
    root.querySelectorAll('.option[data-kind="addon"]').forEach(function (opt) {
      var countEl = opt.querySelector('.count');
      if (!countEl) return;
      var qty = parseInt(countEl.textContent, 10) || 0;
      var price = parseFloat(opt.dataset.price || '0');
      total += qty * price;
      itemCount += qty;
      if (qty > 0) {
        listRows.push({ line: 'Add-on: ' + (opt.dataset.name || 'Item') + ' x' + qty });
      }
    });
    syncAttendeePerSeatFields();
    var totalEl = document.getElementById('totalPrice');
    var labelEl = document.getElementById('orderLabel');
    var summaryEl = document.getElementById('itemsSummary');
    var itemsListEl = document.getElementById('itemsList');
    var btn = document.getElementById('bookBtn');
    var payBlock = document.getElementById('paymentMethodsBlock');
    if (!totalEl || !labelEl || !btn) return;
    if (payBlock) {
      var canPayAny = PAYMENT_STRIPE || PAYMENT_PAYPAL || PAYMENT_RAZORPAY || PAYMENT_SSLCOMMERZ || PAYMENT_OFFLINE_CASH || PAYMENT_OFFLINE_BANK;
      payBlock.style.display = (itemCount > 0 && total > 0 && canPayAny) ? 'block' : 'none';
    }
    syncOfflinePaymentFields();
    if (seatPlanSelected.size > 0) {
      totalEl.textContent = '$' + moneyFmt(total);
      labelEl.textContent = 'Your order';
      if (summaryEl && itemsListEl) {
        summaryEl.style.display = 'block';
        itemsListEl.innerHTML = '';
        listRows.forEach(function (row) {
          var li = document.createElement('li');
          li.textContent = row.line;
          itemsListEl.appendChild(li);
        });
      }
      btn.disabled = false;
      btn.textContent = 'Book now - $' + moneyFmt(total);
    } else {
      totalEl.textContent = '$' + moneyFmt(PRICE_FROM);
      labelEl.textContent = 'Seats from';
      if (summaryEl) summaryEl.style.display = 'none';
      if (itemsListEl) itemsListEl.innerHTML = '';
      btn.disabled = true;
      btn.textContent = 'Select seats to book';
    }
    syncBookGuestSignup();
    return;
  }

  function sumOptions(scopeEl, dayPrefix) {
    scopeEl.querySelectorAll('.option[data-kind="ticket"], .option[data-kind="addon"]').forEach(function(opt) {
      var countEl = opt.querySelector('.count');
      if (!countEl) return;
      var qty = parseInt(countEl.textContent, 10);
      var price = parseFloat(opt.dataset.price || '0');
      var kindRaw = opt.getAttribute('data-kind');
      total += qty * price;
      itemCount += qty;
      if (qty > 0) {
        var label = opt.dataset.name || 'Item';
        var kind = kindRaw === 'addon' ? 'Add-on' : 'Ticket';
        var line = (dayPrefix ? dayPrefix + ' - ' : '') + kind + ': ' + label + ' x' + qty;
        listRows.push({ line: line });
      }
    });
  }

  function collectTicketAssignments(scopeEl, dayPrefix) {
    var out = [];
    if (!scopeEl) return out;
    scopeEl.querySelectorAll('.option[data-kind="ticket"]').forEach(function (opt) {
      var countEl = opt.querySelector('.count');
      if (!countEl) return;
      var qty = parseInt(countEl.textContent, 10) || 0;
      if (qty <= 0) return;
      var label = opt.dataset.name || 'Ticket';
      for (var i = 0; i < qty; i++) {
        out.push({
          dayLabel: dayPrefix || '',
          ticketName: label,
          optionEl: opt
        });
      }
    });
    return out;
  }

  if (root.classList.contains('booking-cart--multi')) {
    root.querySelectorAll('.per-session-cart').forEach(function(dayEl) {
      var label = dayEl.dataset.sessionLabel || '';
      sumOptions(dayEl, label);
    });
    var activeDayEl = root.querySelector('.per-session-cart.is-active') || root.querySelector('.per-session-cart:not([hidden])') || root.querySelector('.per-session-cart');
    var activeDayLabel = activeDayEl ? (activeDayEl.dataset.sessionLabel || '') : '';
    ticketAssignments = collectTicketAssignments(activeDayEl, activeDayLabel);
  } else {
    sumOptions(root, '');
    ticketAssignments = collectTicketAssignments(root, '');
  }
  var totalEl = document.getElementById('totalPrice');
  var labelEl = document.getElementById('orderLabel');
  var summaryEl = document.getElementById('itemsSummary');
  var itemsListEl = document.getElementById('itemsList');
  var btn = document.getElementById('bookBtn');
  var payBlock = document.getElementById('paymentMethodsBlock');
  if (!totalEl || !labelEl || !btn) return;
  if (payBlock) {
    var canPayAny = PAYMENT_STRIPE || PAYMENT_PAYPAL || PAYMENT_RAZORPAY || PAYMENT_SSLCOMMERZ || PAYMENT_OFFLINE_CASH || PAYMENT_OFFLINE_BANK;
    payBlock.style.display = (itemCount > 0 && total > 0 && canPayAny) ? 'block' : 'none';
  }
  syncAttendeePerTicketFields(ticketAssignments);
  syncOfflinePaymentFields();
  if (itemCount > 0) {
    totalEl.textContent = '$' + moneyFmt(total);
    labelEl.textContent = 'Your order';
    if (summaryEl && itemsListEl) {
      summaryEl.style.display = 'block';
      itemsListEl.innerHTML = '';
      listRows.forEach(function(row) {
        var li = document.createElement('li');
        li.textContent = row.line;
        itemsListEl.appendChild(li);
      });
    }
    btn.disabled = false;
    btn.textContent = 'Book now - $' + moneyFmt(total);
  } else {
    totalEl.textContent = '$' + moneyFmt(PRICE_FROM);
    labelEl.textContent = 'Tickets from';
    if (summaryEl) summaryEl.style.display = 'none';
    if (itemsListEl) itemsListEl.innerHTML = '';
    btn.disabled = true;
    btn.textContent = 'Select tickets to book';
  }
  syncBookGuestSignup();
}

function syncBookGuestSignup() {
  var wrap = document.getElementById('bookGuestSignup');
  if (!wrap) return;
  var pm = getSidebarPaymentMethod();
  var onlineGate = !!(PAYMENT_STRIPE || PAYMENT_PAYPAL || PAYMENT_RAZORPAY || PAYMENT_SSLCOMMERZ) && (pm === 'stripe' || pm === 'paypal' || pm === 'razorpay' || pm === 'sslcommerz');
  var totalEl = document.getElementById('totalPrice');
  var labelEl = document.getElementById('orderLabel');
  var chk = document.getElementById('create_account_checkbox');
  var hint = document.getElementById('bookGuestSignupPayHint');
  if (!chk || !totalEl || !labelEl) return;
  var txt = totalEl.textContent.trim();
  var total = parseFloat(txt.replace(/[^0-9.]/g, '')) || 0;
  var hasSelection = labelEl.textContent.trim() === 'Your order';
  var paidOnlineCart = onlineGate && hasSelection && total > 0;
  chk.disabled = false;
  wrap.classList.toggle('book-guest-signup--paid-online', paidOnlineCart);
  if (paidOnlineCart) {
    if (hint) hint.hidden = false;
  } else {
    if (hint) hint.hidden = true;
  }
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-share-event]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var url = window.location.href;
      var titleEl = document.querySelector('.hero--detail h1');
      var title = titleEl ? titleEl.textContent.trim() : document.title;
      if (navigator.share) {
        navigator.share({ title: title, url: url }).catch(function () {});
        return;
      }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).catch(function () {});
      }
    });
  });
  initSeatPlanBooking();
  initBookingDayTabs();
  recalc();
  document.querySelectorAll('aside input[name="payment_method"]').forEach(function (el) {
    el.addEventListener('change', function () {
      syncOfflinePaymentFields();
      syncBookGuestSignup();
    });
  });
});
</script>
@endpush






