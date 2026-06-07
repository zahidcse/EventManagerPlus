@php
    $loc = old('location_type', $event->location_type ?? 'physical');
@endphp
<form method="post" action="{{ route('admin.events.update.location', $event) }}" class="pb-24">
@csrf
@method('PUT')
<div class="p-8 max-w-5xl mx-auto">
@if(session('success'))
<div class="mb-6 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md">{{ session('success') }}</div>
@endif
@include('admin.chunks._event-wizard-steps', ['currentStep' => 2, 'event' => $event])
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm overflow-hidden">
<div class="p-8 border-b border-outline-variant">
<h2 class="text-headline-lg font-semibold text-on-surface">Where is your event happening?</h2>
</div>
<div class="p-8 space-y-10">
@error('location_type')
<p class="text-error text-sm">{{ $message }}</p>
@enderror
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<label class="relative group cursor-pointer block">
<input class="peer sr-only" name="location_type" type="radio" value="physical" @checked($loc === 'physical')/>
<div class="relative p-6 h-full border-2 border-outline-variant rounded-xl group-hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary-fixed/30 transition-all">
<div class="absolute top-4 right-4 opacity-0 peer-checked:opacity-100 text-primary">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check_circle</span>
</div>
<div class="flex items-center space-x-4">
<div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-3xl">location_on</span>
</div>
<div>
<h3 class="font-bold text-on-surface">Physical Venue</h3>
</div>
</div>
</div>
</label>
<label class="relative group cursor-pointer block">
<input class="peer sr-only" name="location_type" type="radio" value="virtual" @checked($loc === 'virtual')/>
<div class="relative p-6 h-full border-2 border-outline-variant rounded-xl group-hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary-fixed/30 transition-all">
<div class="absolute top-4 right-4 opacity-0 peer-checked:opacity-100 text-primary">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check_circle</span>
</div>
<div class="flex items-center space-x-4">
<div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-3xl">videocam</span>
</div>
<div>
<h3 class="font-bold text-on-surface">Virtual Event</h3>
</div>
</div>
</div>
</label>
<label class="relative group cursor-pointer block">
<input class="peer sr-only" name="location_type" type="radio" value="hybrid" @checked($loc === 'hybrid')/>
<div class="relative p-6 h-full border-2 border-outline-variant rounded-xl group-hover:border-primary/50 peer-checked:border-primary peer-checked:bg-primary-fixed/30 transition-all">
<div class="absolute top-4 right-4 opacity-0 peer-checked:opacity-100 text-primary">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">check_circle</span>
</div>
<div class="flex items-center space-x-4">
<div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-3xl">merge</span>
</div>
<div>
<h3 class="font-bold text-on-surface">Hybrid</h3>
</div>
</div>
</div>
</label>
</div>
<div id="event-location-grid" class="grid grid-cols-1 gap-12 {{ $loc === 'physical' ? 'lg:grid-cols-1' : 'lg:grid-cols-2' }}">
<section id="event-location-physical" class="space-y-6 {{ $loc === 'physical' ? 'lg:col-span-2' : '' }}">
<div class="flex items-center space-x-2 pb-2 border-b border-outline-variant">
<span class="material-symbols-outlined text-on-surface-variant">home_pin</span>
<h4 class="font-bold uppercase text-xs tracking-widest text-on-surface-variant">Physical Address</h4>
</div>
<div class="space-y-4">
<div class="space-y-1">
<label class="text-sm font-semibold text-on-surface">Street Address</label>
<input name="venue_street" class="w-full px-4 py-2.5 border border-outline rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition-all" type="text" value="{{ old('venue_street', $event->venue_street) }}" placeholder="e.g. 123 Innovation Drive"/>
@error('venue_street')<p class="text-error text-xs">{{ $message }}</p>@enderror
</div>
<div class="grid grid-cols-2 gap-4">
<div class="space-y-1">
<label class="text-sm font-semibold text-on-surface">City</label>
<input name="venue_city" class="w-full px-4 py-2.5 border border-outline rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition-all" type="text" value="{{ old('venue_city', $event->venue_city) }}" placeholder="San Francisco"/>
@error('venue_city')<p class="text-error text-xs">{{ $message }}</p>@enderror
</div>
<div class="space-y-1">
<label class="text-sm font-semibold text-on-surface">State / Province</label>
<input name="venue_state" class="w-full px-4 py-2.5 border border-outline rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition-all bg-white" type="text" value="{{ old('venue_state', $event->venue_state) }}" placeholder="CA"/>
@error('venue_state')<p class="text-error text-xs">{{ $message }}</p>@enderror
</div>
</div>
<div class="grid grid-cols-2 gap-4">
<div class="space-y-1">
<label class="text-sm font-semibold text-on-surface">Zip / Postal Code</label>
<input name="venue_postal" class="w-full px-4 py-2.5 border border-outline rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition-all" type="text" value="{{ old('venue_postal', $event->venue_postal) }}" placeholder="94105"/>
@error('venue_postal')<p class="text-error text-xs">{{ $message }}</p>@enderror
</div>
<div class="space-y-1">
<label class="text-sm font-semibold text-on-surface">Country</label>
<input name="venue_country" class="w-full px-4 py-2.5 border border-outline rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition-all" type="text" value="{{ old('venue_country', $event->venue_country) }}" placeholder="United States"/>
@error('venue_country')<p class="text-error text-xs">{{ $message }}</p>@enderror
</div>
</div>
</div>
</section>
<section id="event-virtual-block" class="space-y-6 {{ $loc === 'physical' ? 'hidden' : '' }}" aria-hidden="{{ $loc === 'physical' ? 'true' : 'false' }}">
<div class="flex items-center space-x-2 pb-2 border-b border-outline-variant">
<span class="material-symbols-outlined text-on-surface-variant">lan</span>
<h4 class="font-bold uppercase text-xs tracking-widest text-on-surface-variant">Virtual Connection</h4>
</div>
<div class="space-y-4">
<div class="space-y-1">
<label class="text-sm font-semibold text-on-surface">Streaming Platform</label>
<select name="streaming_platform" class="w-full px-4 py-2.5 border border-outline rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none bg-white transition-all">
<option value="">Select Platform</option>
@foreach(['zoom' => 'Zoom', 'teams' => 'Microsoft Teams', 'google_meet' => 'Google Meet', 'custom' => 'Custom URL'] as $val => $label)
<option value="{{ $val }}" @selected(old('streaming_platform', $event->streaming_platform) === $val)>{{ $label }}</option>
@endforeach
</select>
@error('streaming_platform')<p class="text-error text-xs">{{ $message }}</p>@enderror
</div>
<div class="space-y-1">
<label class="text-sm font-semibold text-on-surface">Meeting Link / Access URL</label>
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">link</span>
<input name="meeting_url" class="w-full pl-10 pr-4 py-2.5 border border-outline rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm outline-none transition-all" type="url" value="{{ old('meeting_url', $event->meeting_url) }}" placeholder="https://zoom.us/j/123456789"/>
</div>
@error('meeting_url')<p class="text-error text-xs">{{ $message }}</p>@enderror
</div>
</div>
</section>
</div>
</div>
<div class="px-8 py-6 bg-surface-container border-t border-outline-variant flex items-center justify-between flex-wrap gap-4">
<a href="{{ route('admin.events.edit', $event) }}" class="px-6 py-2 border border-outline-variant text-on-surface font-semibold rounded-lg hover:bg-surface-container-high transition-colors inline-flex items-center">
<span class="material-symbols-outlined mr-2 text-lg">arrow_back</span>
            Back to basics
          </a>
<div class="flex items-center space-x-4">
@include('admin.chunks._event-wizard-save-button', [
    'currentStep' => 2,
    'buttonClass' => 'px-5 py-2.5 rounded-lg border border-outline-variant text-on-surface font-semibold hover:bg-surface-container-low transition-colors',
])
<button type="submit" name="wizard_action" value="continue" class="px-10 py-2 bg-primary text-white font-semibold rounded-lg hover:bg-primary-container active:scale-95 transition-all shadow-md inline-flex items-center">
              Next: Ticketing
            </button>
</div>
</div>
</div>
</div>
</form>
<script>
(function () {
    var radios = document.querySelectorAll('input[name="location_type"]');
    var grid = document.getElementById('event-location-grid');
    var physical = document.getElementById('event-location-physical');
    var virtualBlock = document.getElementById('event-virtual-block');
    if (!grid || !physical || !virtualBlock) return;
    function sync() {
        var checked = document.querySelector('input[name="location_type"]:checked');
        var v = checked ? checked.value : 'physical';
        var isPhysicalOnly = v === 'physical';
        if (isPhysicalOnly) {
            virtualBlock.classList.add('hidden');
            virtualBlock.setAttribute('aria-hidden', 'true');
            grid.classList.remove('lg:grid-cols-2');
            grid.classList.add('lg:grid-cols-1');
            physical.classList.add('lg:col-span-2');
        } else {
            virtualBlock.classList.remove('hidden');
            virtualBlock.setAttribute('aria-hidden', 'false');
            grid.classList.remove('lg:grid-cols-1');
            grid.classList.add('lg:grid-cols-2');
            physical.classList.remove('lg:col-span-2');
        }
    }
    radios.forEach(function (r) { r.addEventListener('change', sync); });
    sync();
})();
</script>
