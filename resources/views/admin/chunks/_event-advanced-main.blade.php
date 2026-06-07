@php
  $pdfFieldLabels = [
    'company_logo' => 'Company logo',
    'event_name' => 'Event name',
    'organizer_name' => 'Organizer name',
    'company_name' => 'Company name',
    'event_datetime' => 'Event date & time',
    'event_location' => 'Event location',
    'location_type' => 'Location type',
    'attendee_name' => 'Attendee name',
    'attendee_email' => 'Attendee email',
    'attendee_phone' => 'Attendee phone',
    'ticket_type' => 'Ticket type',
    'seat_number' => 'Seat number',
    'booking_id' => 'Booking ID',
    'session_date' => 'Session date',
    'tier_price' => 'Tier price',
    'order_status' => 'Order status',
    'payment_reference' => 'Payment reference',
    'notes' => 'Notes',
    'checkin_qr' => 'Check-in QR code',
  ];
  $attendeeFieldDefinitions = \App\Models\Event::attendeeFieldDefinitions();
  $pdfFields = $errors->any()
    ? old('ticket_pdf_fields', $event->ticketPdfFieldsResolved())
    : $event->ticketPdfFieldsResolved();
  if (! is_array($pdfFields)) {
    $pdfFields = $event->ticketPdfFieldsResolved();
  }
  $attendeeSettings = $errors->any()
    ? old('attendee_settings', $event->attendeeSettingsResolved())
    : $event->attendeeSettingsResolved();
  if (! is_array($attendeeSettings)) {
    $attendeeSettings = $event->attendeeSettingsResolved();
  }
  $attendeeFields = is_array($attendeeSettings['fields'] ?? null) ? $attendeeSettings['fields'] : [];
@endphp
<form method="post" action="{{ route('admin.events.update.content', $event) }}" id="event-advanced-form" enctype="multipart/form-data">
@csrf
@method('PUT')
<input type="hidden" name="wizard_panel" value="advanced"/>
<div class="p-8 max-w-6xl mx-auto pb-36">
@if(session('success'))
<div class="mb-6 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md">{{ session('success') }}</div>
@endif
@include('admin.chunks._event-wizard-steps', ['currentStep' => 4, 'event' => $event])
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
<div class="lg:col-span-8 space-y-6">
<section class="rounded-2xl border border-outline-variant bg-white shadow-sm overflow-hidden">
<div class="px-6 py-5 border-b border-outline-variant bg-surface-container-low/50 flex items-center gap-3">
<span class="material-symbols-outlined text-primary bg-primary-fixed/30 p-2 rounded-lg">alternate_email</span>
<h2 class="text-lg font-bold text-on-surface">Email Communications</h2>
</div>
<div class="p-6 space-y-4">
<div>
<label class="block text-[11px] font-bold text-outline uppercase mb-1">Email Subject</label>
<input name="email_subject" class="w-full border border-outline-variant rounded-lg p-2 text-sm font-medium focus:ring-2 focus:ring-primary/20" type="text" value="{{ old('email_subject', $event->email_subject ?? 'Registration Confirmed: {event_name}') }}"/>
@error('email_subject')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-[11px] font-bold text-outline uppercase mb-1">Email Body</label>
<textarea name="email_body" rows="6" class="w-full border border-outline-variant rounded-lg p-3 text-xs text-on-surface-variant leading-relaxed focus:ring-2 focus:ring-primary/20">{{ old('email_body', $event->email_body ?? 'Hi {attendee_name}, your registration for {event_name} on {event_date} is confirmed.') }}</textarea>
@error('email_body')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
</div>
</section>

<section class="rounded-2xl border border-outline-variant bg-white shadow-sm overflow-hidden relative">
<div class="px-6 py-5 border-b border-outline-variant bg-surface-container-low/50 flex items-center justify-between gap-3">
<h2 class="text-lg font-bold text-on-surface">PDF Ticket Fields</h2>
@if($editionIsFree ?? false)
@include('admin.chunks._premium-badge')
@endif
</div>
<div class="p-6 space-y-6 {{ ($editionIsFree ?? false) ? 'opacity-60 pointer-events-none select-none' : '' }}">
<div class="rounded-xl border border-outline-variant/80 bg-surface-container-low/30 px-4 py-4 space-y-3">
<label class="block text-body-md font-semibold text-on-surface">Ticket logo</label>
@if(filled($event->ticket_logo_path))
<div class="flex flex-wrap items-center gap-3">
<img src="{{ $event->ticketLogoPublicUrl() }}" alt="" class="h-14 w-auto max-w-[160px] rounded border border-outline-variant object-contain bg-white p-1"/>
<label class="inline-flex items-center gap-2 text-body-sm text-on-surface-variant cursor-pointer">
<input type="checkbox" name="clear_ticket_logo" value="1" class="rounded border-outline-variant text-primary" @checked(old('clear_ticket_logo'))/>
Remove current logo
</label>
</div>
@endif
<input type="file" name="ticket_logo" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-semibold"/>
@error('ticket_logo')<p class="text-error text-xs">{{ $message }}</p>@enderror
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
@foreach($pdfFieldLabels as $key => $label)
  <div class="flex items-center justify-between rounded-xl border border-outline-variant/80 bg-surface-container-low/30 px-4 py-3">
    <label for="pdf-field-{{ $key }}" class="text-body-md text-on-surface cursor-pointer">{{ $label }}</label>
    <span class="ml-4 shrink-0">
      @include('admin.chunks._toggle-switch', [
        'name' => 'ticket_pdf_fields['.$key.']',
        'id' => 'pdf-field-'.$key,
        'checked' => filter_var($pdfFields[$key] ?? false, FILTER_VALIDATE_BOOLEAN),
      ])
    </span>
  </div>
@endforeach
</div>
</div>
</section>

<section class="rounded-2xl border border-outline-variant bg-white shadow-sm overflow-hidden relative">
<div class="px-6 py-5 border-b border-outline-variant bg-surface-container-low/50 flex items-center justify-between gap-3">
<h2 class="text-lg font-bold text-on-surface">Attendee Settings</h2>
@if($editionIsFree ?? false)
@include('admin.chunks._premium-badge')
@endif
</div>
<div class="p-6 space-y-5 {{ ($editionIsFree ?? false) ? 'opacity-60 pointer-events-none select-none' : '' }}">
<div class="flex items-center justify-between rounded-xl border border-outline-variant/80 bg-surface-container-low/30 px-4 py-3">
  <label for="attendee-settings-enabled" class="text-body-md text-on-surface cursor-pointer">Collect attendee information per ticket quantity</label>
  <span class="ml-4 shrink-0">
    @include('admin.chunks._toggle-switch', [
      'name' => 'attendee_settings[enabled]',
      'id' => 'attendee-settings-enabled',
      'checked' => filter_var($attendeeSettings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ])
  </span>
</div>
@error('attendee_settings.enabled')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror

<div id="attendee-settings-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
@foreach($attendeeFieldDefinitions as $key => $definition)
  <div class="flex items-center justify-between rounded-xl border border-outline-variant/80 bg-surface-container-low/30 px-4 py-3">
    <label for="attendee-field-{{ $key }}" class="text-body-md text-on-surface cursor-pointer">{{ $definition['label'] ?? $key }}</label>
    <span class="ml-4 shrink-0">
      @include('admin.chunks._toggle-switch', [
        'name' => 'attendee_settings[fields]['.$key.']',
        'id' => 'attendee-field-'.$key,
        'checked' => filter_var($attendeeFields[$key] ?? false, FILTER_VALIDATE_BOOLEAN),
        'inputClass' => 'attendee-setting-field',
      ])
    </span>
  </div>
@endforeach
</div>
</div>
</section>
</div>

<aside class="lg:col-span-4 lg:sticky lg:top-24 space-y-6">
<section class="rounded-2xl border border-outline-variant bg-white shadow-sm overflow-hidden">
<div class="px-6 py-5 border-b border-outline-variant bg-surface-container-low/50">
<h2 class="text-lg font-bold text-on-surface">Tax &amp; Purchase Rules</h2>
</div>
<div class="p-6 space-y-5">
<div>
<label class="block text-label-md font-semibold text-on-surface mb-2">Tax &amp; fees</label>
<select name="fee_handling" class="w-full rounded-lg border border-outline-variant bg-white px-3 py-2.5 text-body-sm outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
<option value="pass_to_buyer" @selected(old('fee_handling', $event->fee_handling ?? 'pass_to_buyer') === 'pass_to_buyer')>Pass to buyer</option>
<option value="absorb" @selected(old('fee_handling', $event->fee_handling) === 'absorb')>Absorb fees</option>
</select>
@error('fee_handling')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-2">Max per customer</label>
<input name="max_tickets_per_customer" class="w-full rounded-lg border border-outline-variant bg-white px-3 py-2.5 text-body-sm text-center outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" type="number" min="1" max="99" value="{{ old('max_tickets_per_customer', $event->max_tickets_per_customer ?? 4) }}"/>
@error('max_tickets_per_customer')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
</div>
</section>

<section class="rounded-2xl border border-outline-variant bg-white shadow-sm overflow-hidden relative">
<div class="px-6 py-5 border-b border-outline-variant bg-surface-container-low/50 flex items-center gap-3">
<span class="material-symbols-outlined text-red-500 bg-red-500/10 p-2 rounded-lg">event_seat</span>
<div class="flex-1 min-w-0 flex items-center justify-between gap-3">
<h2 class="text-lg font-bold text-on-surface">Seat plan</h2>
@if($editionIsFree ?? false)
@include('admin.chunks._premium-badge')
@endif
</div>
</div>
<div class="p-6 space-y-4 opacity-60 pointer-events-none select-none">
<div class="rounded-xl border border-outline-variant/80 bg-surface-container-low/40 px-4 py-3 text-body-sm text-on-surface-variant leading-relaxed">
Visual seat layouts and seat-based booking are available in the premium version.
</div>
<div class="flex items-start justify-between gap-4 rounded-xl border border-outline-variant/80 bg-surface-container-low/30 px-4 py-4">
<label class="min-w-0">
<span class="text-body-md font-semibold text-on-surface block">Enable seat plan for this event</span>
</label>
<span class="shrink-0 pt-0.5">
@include('admin.chunks._toggle-switch', [
  'name' => 'seat_plan_enabled',
  'id' => 'seat-plan-enabled-toggle',
  'checked' => false,
  'disabled' => true,
])
</span>
</div>
</div>
</section>
</aside>
</div>

<div class="fixed bottom-0 right-0 left-sidebar-width bg-white/95 backdrop-blur-sm border-t border-outline-variant px-6 sm:px-8 py-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 z-40">
<a href="{{ route('admin.events.edit.content', $event) }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-secondary font-semibold hover:bg-surface-container-low rounded-xl transition-colors order-2 sm:order-1">
<span class="material-symbols-outlined text-[20px]">arrow_back</span>
Back to Content
</a>
<div class="flex items-center justify-end gap-3 order-1 sm:order-2 flex-wrap">
@include('admin.chunks._event-wizard-seat-plan-link')
<button type="submit" name="wizard_action" value="continue" class="px-6 py-2.5 rounded-lg bg-outline-variant text-on-surface font-bold hover:bg-surface-container transition-colors">
Save Advanced
</button>
<button type="submit" name="wizard_action" value="publish" class="px-6 py-2.5 rounded-lg bg-primary text-white font-bold hover:bg-primary-container transition-colors inline-flex items-center justify-center gap-2 shadow-md shadow-primary/20">
<span class="material-symbols-outlined text-[20px]">rocket_launch</span>
Publish event
</button>
</form>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var enabledToggle = document.getElementById('attendee-settings-enabled');
  var fieldsWrap = document.getElementById('attendee-settings-fields');
  if (!enabledToggle || !fieldsWrap) return;

  var fieldToggles = fieldsWrap.querySelectorAll('.attendee-setting-field');
  function syncAttendeeSettingsState() {
    var enabled = !!enabledToggle.checked;
    fieldsWrap.style.opacity = enabled ? '1' : '0.55';
    fieldToggles.forEach(function (el) {
      el.disabled = !enabled;
    });
  }

  enabledToggle.addEventListener('change', syncAttendeeSettingsState);
  syncAttendeeSettingsState();
});
</script>
</div>
