<div class="ticket-tier-row rounded-2xl border border-outline-variant bg-surface-container-lowest/40 overflow-hidden transition-shadow hover:shadow-md hover:border-outline-variant">
<div class="flex items-center justify-between gap-3 px-5 py-3.5 bg-white border-b border-outline-variant/80">
<div class="flex items-center gap-3 min-w-0">
<span class="ticket-tier-badge flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary text-label-md font-bold tabular-nums">1</span>
<div class="min-w-0">
<p class="text-label-md font-semibold text-on-surface truncate">Ticket tier</p>
<p class="text-[11px] text-on-surface-variant">Optional early bird and sales end can be expanded below.</p>
</div>
</div>
<button type="button" class="ticket-remove-btn inline-flex items-center justify-center rounded-lg p-2 text-on-surface-variant hover:bg-error-container/20 hover:text-error transition-colors {{ $showRemove ? '' : 'hidden' }}" aria-label="Remove ticket type" {{ $showRemove ? '' : 'disabled' }}>
<span class="material-symbols-outlined text-[22px]">delete</span>
</button>
</div>
<div class="p-5 sm:p-6">
@php
    $ebPrice = $t['early_bird_price'] ?? '';
    $ebEnd = $t['early_bird_ends_at'] ?? '';
    $salesStart = $t['sales_start'] ?? '';
    $salesEnd = $t['sales_end'] ?? '';
    $hasEarlyBird = (
        ($salesStart !== '' && $salesStart !== null)
        || ($ebPrice !== '' && $ebPrice !== null)
        || ($ebEnd !== '' && $ebEnd !== null)
    );
    $hasSalesWindow = ($salesEnd !== '' && $salesEnd !== null);
@endphp
<div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end sm:gap-3 mb-4">
<div class="min-w-0 w-full sm:flex-[1.2] sm:min-w-[120px]">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Ticket type <span class="text-error">*</span></label>
<input name="tickets[{{ $idx }}][name]" type="text" value="{{ $t['name'] ?? '' }}" placeholder="e.g. Early Bird, General Admission" class="ticket-field-name w-full rounded-xl border border-outline-variant bg-white px-3 py-2.5 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all placeholder:text-on-surface-variant/60"/>
</div>
<div class="min-w-0 w-full sm:flex-1 sm:min-w-[7rem] sm:max-w-[12rem]">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Price <span class="font-normal text-on-surface-variant">(USD)</span></label>
<div class="relative">
<span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant font-medium text-sm">$</span>
<input name="tickets[{{ $idx }}][price]" type="number" step="0.01" min="0" value="{{ $t['price'] ?? '' }}" placeholder="0.00" class="ticket-field-price w-full rounded-xl border border-outline-variant bg-white py-2.5 pl-7 pr-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 tabular-nums"/>
</div>
</div>
<div class="min-w-0 w-full sm:flex-1 sm:min-w-[5rem] sm:max-w-[8rem] ticket-per-tier-qty-wrap">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Quantity</label>
<input name="tickets[{{ $idx }}][quantity]" type="number" min="0" value="{{ $t['quantity'] ?? '' }}" placeholder="0" class="ticket-field-qty w-full rounded-xl border border-outline-variant bg-white px-3 py-2.5 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 tabular-nums"/>
</div>
</div>
<div class="grid grid-cols-1 gap-3 lg:grid-cols-2 lg:gap-4 lg:items-start">
<div class="relative min-w-0">
@if($editionIsFree ?? false)
<div class="absolute top-2 right-2 z-10">@include('admin.chunks._premium-badge')</div>
@endif
<details class="ticket-early-bird group min-w-0 rounded-xl border border-outline-variant/80 bg-surface-container-low/30 open:bg-white open:border-outline-variant transition-colors {{ ($editionIsFree ?? false) ? 'opacity-60 pointer-events-none select-none' : '' }}" {{ $hasEarlyBird ? 'open' : '' }}>
<summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-3 text-label-md font-semibold text-on-surface outline-none hover:bg-surface-container-low/50 [&::-webkit-details-marker]:hidden">
<span class="flex items-center gap-2 min-w-0">
<span class="material-symbols-outlined text-primary text-[22px] shrink-0">sell</span>
<span class="truncate">Early bird <span class="font-normal text-on-surface-variant">(optional)</span></span>
</span>
<span class="material-symbols-outlined text-on-surface-variant shrink-0 transition-transform duration-200 group-open:rotate-180">expand_more</span>
</summary>
<div class="border-t border-outline-variant/60 px-4 pb-4 pt-2">
<p class="text-body-sm text-on-surface-variant mb-3">Discounted price between the dates below. Leave blank to skip early-bird pricing.</p>
<div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end sm:gap-3">
<div class="min-w-0 w-full sm:flex-1 sm:min-w-[8.5rem]">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Early bird start</label>
<input name="tickets[{{ $idx }}][sales_start]" type="date" value="{{ $salesStart }}" class="w-full rounded-xl border border-outline-variant bg-white px-2 py-2.5 text-body-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
</div>
<div class="min-w-0 w-full sm:flex-1 sm:min-w-[6rem]">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Early bird price <span class="font-normal text-on-surface-variant">(USD)</span></label>
<div class="relative">
<span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant font-medium text-sm">$</span>
<input name="tickets[{{ $idx }}][early_bird_price]" type="number" step="0.01" min="0" value="{{ $ebPrice }}" placeholder="—" class="w-full rounded-xl border border-outline-variant bg-white py-2.5 pl-7 pr-2 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 tabular-nums"/>
</div>
</div>
<div class="min-w-0 w-full sm:flex-1 sm:min-w-[8.5rem]">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Early bird end</label>
<input name="tickets[{{ $idx }}][early_bird_ends_at]" type="date" value="{{ $ebEnd }}" class="w-full rounded-xl border border-outline-variant bg-white px-2 py-2.5 text-body-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
</div>
</div>
</div>
</details>
</div>
<details class="ticket-sales-window group min-w-0 rounded-xl border border-outline-variant/80 bg-surface-container-low/30 open:bg-white open:border-outline-variant transition-colors" {{ $hasSalesWindow ? 'open' : '' }}>
<summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-3 text-label-md font-semibold text-on-surface outline-none hover:bg-surface-container-low/50 [&::-webkit-details-marker]:hidden">
<span class="flex items-center gap-2 min-w-0">
<span class="material-symbols-outlined text-primary text-[22px] shrink-0">calendar_clock</span>
<span class="truncate">Sales end <span class="font-normal text-on-surface-variant">(optional)</span></span>
</span>
<span class="material-symbols-outlined text-on-surface-variant shrink-0 transition-transform duration-200 group-open:rotate-180">expand_more</span>
</summary>
<div class="border-t border-outline-variant/60 px-4 pb-4 pt-2">
<p class="text-body-sm text-on-surface-variant mb-3">When this tier stops being sold. Leave blank for no end date.</p>
<div>
<label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Sales end</label>
<input name="tickets[{{ $idx }}][sales_end]" type="date" value="{{ $salesEnd }}" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-body-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
</div>
</div>
</details>
</div>
</div>
</div>
