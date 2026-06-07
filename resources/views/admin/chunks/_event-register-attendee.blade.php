<div class="max-w-7xl mx-auto px-8 pt-8 pb-2">
<section class="rounded-2xl border border-outline-variant bg-white shadow-sm overflow-hidden mb-8" id="staff-register-attendee">
<div class="px-6 py-5 border-b border-outline-variant bg-surface-container-low/50">
<h2 class="text-lg font-bold text-on-surface">Participant &amp; tickets</h2>
<p class="text-body-sm text-on-surface-variant mt-0.5">Confirm a booking without payment—guest walk-ins or existing customer accounts. Sales windows do not apply; inventory and venue capacity still do.</p>
</div>
<div class="p-6">
<form method="post" action="{{ route('admin.events.registrations.store', $event) }}" class="space-y-6">
@csrf
<fieldset class="space-y-3">
<legend class="text-label-md font-semibold text-on-surface mb-2">Participant type</legend>
<label class="flex items-center gap-2 cursor-pointer">
<input type="radio" name="registration_kind" value="guest" class="rounded border-outline-variant text-primary focus:ring-primary/25" @checked(old('registration_kind', 'guest') === 'guest') required/>
<span class="text-body-md">Guest / walk-in</span>
</label>
<label class="flex items-center gap-2 cursor-pointer">
<input type="radio" name="registration_kind" value="registered_user" class="rounded border-outline-variant text-primary focus:ring-primary/25" @checked(old('registration_kind') === 'registered_user') required/>
<span class="text-body-md">Registered site user</span>
</label>
@error('registration_kind')
<p class="text-error text-sm">{{ $message }}</p>
@enderror
</fieldset>

<div id="staff-reg-guest-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4 @if(old('registration_kind') === 'registered_user') hidden @endif">
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Guest name</label>
<input type="text" name="guest_name" value="{{ old('guest_name') }}" autocomplete="name" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
@error('guest_name')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Guest email</label>
<input type="email" name="guest_email" value="{{ old('guest_email') }}" autocomplete="email" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
@error('guest_email')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
</div>

<div id="staff-reg-user-field" class="@if(old('registration_kind') !== 'registered_user') hidden @endif">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Customer account</label>
<select name="user_id" class="w-full max-w-xl rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
<option value="">— Select user —</option>
@foreach(($registrationCustomers ?? collect()) as $u)
<option value="{{ $u->id }}" @selected((string) old('user_id') === (string) $u->id)>{{ $u->name }} — {{ $u->email }}</option>
@endforeach
</select>
@error('user_id')
<p class="text-error text-sm mt-2">{{ $message }}</p>
@enderror
</div>

<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Phone <span class="text-on-surface-variant font-normal">(optional)</span></label>
<input type="text" name="phone" value="{{ old('phone') }}" autocomplete="tel" class="w-full max-w-md rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
@error('phone')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>

@php
  $staffOcc = $event->occurrenceDateStringsForStaffRegistration();
  $staffPerDay = ($event->schedule_type ?? 'single') !== 'single' && count($staffOcc) > 0;
@endphp

@if($event->tickets->isEmpty())
<p class="text-body-sm text-error">Save ticket tiers first, then you can register attendees here.</p>
@elseif($staffPerDay)
<p class="text-body-sm text-on-surface-variant mb-4">Set ticket tiers and add-ons separately for each session day (days left empty are skipped).</p>
@foreach($staffOcc as $d)
@php $dc = \Illuminate\Support\Carbon::parse($d); @endphp
<div class="rounded-xl border border-outline-variant/80 bg-surface-container-low/30 p-5 space-y-4 mb-4">
<p class="text-label-md font-semibold text-on-surface">{{ $dc->format('l, M j, Y') }}</p>
<p class="text-label-md font-semibold text-on-surface mt-2">Tickets</p>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
@foreach($event->tickets as $t)
<div>
<label class="block text-body-sm text-on-surface-variant mb-1">{{ $t->name }}</label>
<input type="number" name="admin_qty_by_date[{{ $d }}][{{ $t->id }}]" min="0" max="100" value="{{ (int) old('admin_qty_by_date.'.$d.'.'.$t->id, 0) }}" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-body-md tabular-nums outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
</div>
@endforeach
</div>
@if($event->additionalServices->isNotEmpty())
<p class="text-label-md font-semibold text-on-surface">Add-ons (this day)</p>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
@foreach($event->additionalServices as $s)
@php $sRem = $s->remainingForSale(); $sMax = $sRem !== null ? min(50, $sRem) : 50; @endphp
<div>
<label class="block text-body-sm text-on-surface-variant mb-1">{{ $s->name }}@if($sRem !== null) <span class="text-on-surface-variant/80">({{ $sRem }} left)</span>@endif</label>
<input type="number" name="admin_addon_qty_by_date[{{ $d }}][{{ $s->id }}]" min="0" max="{{ $sMax }}" value="{{ (int) old('admin_addon_qty_by_date.'.$d.'.'.$s->id, 0) }}" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-body-md tabular-nums outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
</div>
@endforeach
</div>
@endif
@error('admin_qty_by_date.'.$d)
<p class="text-error text-sm">{{ $message }}</p>
@enderror
</div>
@endforeach
@error('admin_qty')
<p class="text-error text-sm">{{ $message }}</p>
@enderror
@else
<div class="rounded-xl border border-outline-variant/80 bg-surface-container-low/30 p-5 space-y-4">
<p class="text-label-md font-semibold text-on-surface">Tickets</p>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
@foreach($event->tickets as $t)
<div>
<label class="block text-body-sm text-on-surface-variant mb-1">{{ $t->name }}</label>
<input type="number" name="admin_qty[{{ $t->id }}]" min="0" max="100" value="{{ old('admin_qty.'.$t->id, 0) }}" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-body-md tabular-nums outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
</div>
@endforeach
</div>
@error('admin_qty')
<p class="text-error text-sm">{{ $message }}</p>
@enderror
</div>
@endif

@if(!$staffPerDay && $event->additionalServices->isNotEmpty())
<div class="rounded-xl border border-outline-variant/80 bg-surface-container-low/30 p-5 space-y-4">
<p class="text-label-md font-semibold text-on-surface">Add-ons</p>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
@foreach($event->additionalServices as $s)
@php $sRem = $s->remainingForSale(); $sMax = $sRem !== null ? min(50, $sRem) : 50; @endphp
<div>
<label class="block text-body-sm text-on-surface-variant mb-1">{{ $s->name }}@if($sRem !== null) <span class="text-on-surface-variant/80">({{ $sRem }} left)</span>@endif</label>
<input type="number" name="admin_addon_qty[{{ $s->id }}]" min="0" max="{{ $sMax }}" value="{{ old('admin_addon_qty.'.$s->id, 0) }}" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-body-md tabular-nums outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
</div>
@endforeach
</div>
@error('admin_addon_qty')
<p class="text-error text-sm">{{ $message }}</p>
@enderror
</div>
@endif

<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Internal notes <span class="text-on-surface-variant font-normal">(optional)</span></label>
<textarea name="admin_notes" rows="2" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15" placeholder="e.g. VIP list, comp ticket, paid cash at door">{{ old('admin_notes') }}</textarea>
@error('admin_notes')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>

<label class="flex items-start gap-3 cursor-pointer select-none">
<input type="checkbox" name="send_confirmation" value="1" class="mt-1 rounded border-outline-variant text-primary focus:ring-primary/25" @checked(old('send_confirmation'))/>
<span class="text-body-sm text-on-surface-variant"><span class="font-semibold text-on-surface">Send confirmation email</span> with ticket PDFs to the attendee (and site contact if configured).</span>
</label>
@error('send_confirmation')
<p class="text-error text-sm">{{ $message }}</p>
@enderror

<div class="flex flex-wrap items-center gap-3 pt-2">
<button type="submit" class="px-6 py-2.5 bg-secondary text-white font-bold rounded-xl hover:opacity-95 transition-all shadow-md shadow-secondary/25 active:scale-[0.98] inline-flex items-center gap-2 disabled:opacity-50" @disabled($event->tickets->isEmpty())>
<span class="material-symbols-outlined text-[20px]">person_add</span>
Confirm registration
</button>
@if($errors->has('guest_name') || $errors->has('guest_email') || $errors->has('user_id'))
<p class="text-body-sm text-error">Fix the participant fields above.</p>
@endif
</div>
</form>
</div>
</section>
</div>
<script>
(function () {
  var guestRadio = document.querySelector('#staff-register-attendee input[name="registration_kind"][value="guest"]');
  var userRadio = document.querySelector('#staff-register-attendee input[name="registration_kind"][value="registered_user"]');
  var guestFields = document.getElementById('staff-reg-guest-fields');
  var userField = document.getElementById('staff-reg-user-field');
  if (!guestRadio || !userRadio || !guestFields || !userField) return;
  function sync() {
    var isUser = userRadio.checked;
    guestFields.classList.toggle('hidden', isUser);
    userField.classList.toggle('hidden', !isUser);
  }
  guestRadio.addEventListener('change', sync);
  userRadio.addEventListener('change', sync);
  sync();
})();
</script>
