@php
    $ev = $event ?? null;
    $loc = old('location_type', $ev?->location_type ?? 'physical');
    $addressDefault = trim((string) ($ev?->fullVenueAddressLine() ?? ''));
    if ($addressDefault === '' && $ev) {
        $addressDefault = (string) ($ev->venue_street ?? '');
    }
@endphp
<div class="space-y-4 border-t border-outline-variant/70 pt-5">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Event type</label>
@error('location_type')
<p class="text-error text-sm">{{ $message }}</p>
@enderror
<div class="flex flex-col gap-2">
<label class="flex items-center gap-2 cursor-pointer text-body-sm text-on-surface">
<input class="text-primary focus:ring-primary shrink-0" name="location_type" type="radio" value="physical" @checked($loc === 'physical')/>
<span>Physical venue</span>
</label>
<label class="flex items-center gap-2 cursor-pointer text-body-sm text-on-surface">
<input class="text-primary focus:ring-primary shrink-0" name="location_type" type="radio" value="virtual" @checked($loc === 'virtual')/>
<span>Virtual</span>
</label>
<label class="flex items-center gap-2 cursor-pointer text-body-sm text-on-surface">
<input class="text-primary focus:ring-primary shrink-0" name="location_type" type="radio" value="hybrid" @checked($loc === 'hybrid')/>
<span>Hybrid</span>
</label>
</div>
<div class="space-y-4">
<div id="basic-loc-physical" class="space-y-3 {{ $loc === 'virtual' ? 'hidden' : '' }}">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider" for="event-basic-event-location">Event Location</label>
<textarea id="event-basic-event-location" name="venue_street" rows="3" class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-sm outline-none focus:ring-2 focus:ring-primary/20 resize-y min-h-[88px] @error('venue_street') border-error @enderror" placeholder="e.g. Convention Center, 100 Main St, Austin, TX 73301">{{ old('venue_street', $addressDefault) }}</textarea>
@error('venue_street')<p class="text-error text-sm">{{ $message }}</p>@enderror
</div>
<div id="basic-loc-virtual" class="space-y-3 pt-1 {{ $loc === 'virtual' || $loc === 'hybrid' ? '' : 'hidden' }}">
<p class="text-[11px] font-bold text-outline uppercase tracking-wide">Virtual</p>
<div>
<label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Platform</label>
<select name="streaming_platform" class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-sm bg-white outline-none focus:ring-2 focus:ring-primary/20">
<option value="">— Select —</option>
@foreach(['zoom' => 'Zoom', 'teams' => 'Microsoft Teams', 'google_meet' => 'Google Meet', 'custom' => 'Custom URL'] as $val => $label)
<option value="{{ $val }}" @selected(old('streaming_platform', $ev?->streaming_platform) === $val)>{{ $label }}</option>
@endforeach
</select>
@error('streaming_platform')<p class="text-error text-xs mt-0.5">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Meeting URL</label>
<input name="meeting_url" class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-sm outline-none focus:ring-2 focus:ring-primary/20" type="url" value="{{ old('meeting_url', $ev?->meeting_url) }}" placeholder="https://"/>
@error('meeting_url')<p class="text-error text-xs mt-0.5">{{ $message }}</p>@enderror
</div>
</div>
</div>
</div>
<script>
(function () {
  var form = document.getElementById('event-basic-form');
  if (!form) return;
  var radios = form.querySelectorAll('input[name="location_type"]');
  var virtualBlock = document.getElementById('basic-loc-virtual');
  var physicalBlock = document.getElementById('basic-loc-physical');
  if (!virtualBlock || !physicalBlock) return;
  function sync() {
    var checked = form.querySelector('input[name="location_type"]:checked');
    var v = checked ? checked.value : 'physical';
    if (v === 'physical') {
      virtualBlock.classList.add('hidden');
      physicalBlock.classList.remove('hidden');
    } else if (v === 'virtual') {
      virtualBlock.classList.remove('hidden');
      physicalBlock.classList.add('hidden');
    } else {
      virtualBlock.classList.remove('hidden');
      physicalBlock.classList.remove('hidden');
    }
  }
  radios.forEach(function (r) { r.addEventListener('change', sync); });
  sync();
})();
</script>
