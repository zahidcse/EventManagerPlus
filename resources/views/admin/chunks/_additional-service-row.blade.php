<div class="additional-service-row rounded-2xl border border-outline-variant bg-surface-container-lowest/40 overflow-hidden transition-shadow hover:shadow-md hover:border-outline-variant">
<div class="flex items-center justify-between gap-3 px-5 py-3.5 bg-white border-b border-outline-variant/80">
<div class="flex items-center gap-3 min-w-0">
<span class="additional-service-badge flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-secondary/10 text-secondary text-label-md font-bold tabular-nums">1</span>
<div class="min-w-0">
<p class="text-label-md font-semibold text-on-surface truncate">Add-on</p>
<p class="text-[11px] text-on-surface-variant">Optional extras — e.g. T-shirt, mug, cup. Empty rows are ignored on save. Quantity 0 = unlimited.</p>
</div>
</div>
<button type="button" class="additional-service-remove-btn inline-flex items-center justify-center rounded-lg p-2 text-on-surface-variant hover:bg-error-container/20 hover:text-error transition-colors {{ $showRemove ? '' : 'hidden' }}" aria-label="Remove add-on" {{ $showRemove ? '' : 'disabled' }}>
<span class="material-symbols-outlined text-[22px]">delete</span>
</button>
</div>
<div class="p-5 sm:p-6">
<div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4 sm:items-end">
<div class="sm:col-span-5 min-w-0">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Service name</label>
<input name="additional_services[{{ $idx }}][name]" type="text" value="{{ $s['name'] ?? '' }}" placeholder="e.g. Event T-shirt, Mug, Cup" class="additional-service-field-name w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 transition-all placeholder:text-on-surface-variant/60"/>
</div>
<div class="sm:col-span-3 min-w-0">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Price (USD)</label>
<div class="relative">
<span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant font-medium">$</span>
<input name="additional_services[{{ $idx }}][price]" type="number" step="0.01" min="0" value="{{ $s['price'] ?? '' }}" placeholder="0.00" class="additional-service-field-price w-full rounded-xl border border-outline-variant bg-white py-3 pl-8 pr-4 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 tabular-nums"/>
</div>
</div>
<div class="sm:col-span-4 min-w-0">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Quantity</label>
<input name="additional_services[{{ $idx }}][quantity]" type="number" min="0" value="{{ $s['quantity'] ?? '' }}" placeholder="0 = unlimited" class="additional-service-field-qty w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 tabular-nums"/>
</div>
</div>
</div>
</div>
