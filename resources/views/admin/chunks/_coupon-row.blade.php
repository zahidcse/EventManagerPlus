<div class="coupon-row rounded-2xl border border-outline-variant bg-surface-container-lowest/40 overflow-hidden transition-shadow hover:shadow-md hover:border-outline-variant">
<div class="flex items-center justify-between gap-3 px-5 py-3.5 bg-white border-b border-outline-variant/80">
<div class="flex items-center gap-3 min-w-0">
<span class="coupon-row-badge flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary text-label-md font-bold tabular-nums">1</span>
<div class="min-w-0">
<p class="text-label-md font-semibold text-on-surface truncate">Coupon</p>
</div>
</div>
<button type="button" class="coupon-remove-btn inline-flex items-center justify-center rounded-lg p-2 text-on-surface-variant hover:bg-error-container/20 hover:text-error transition-colors {{ $showRemove ? '' : 'hidden' }}" aria-label="Remove coupon" {{ $showRemove ? '' : 'disabled' }}>
<span class="material-symbols-outlined text-[22px]">delete</span>
</button>
</div>
<div class="p-5 sm:p-6 space-y-4">
<input type="hidden" name="coupons[{{ $idx }}][id]" value="{{ old('coupons.'.$idx.'.id', $c['id'] ?? '') }}"/>
<div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4 sm:items-end">
<div class="sm:col-span-4 min-w-0">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Code</label>
<input name="coupons[{{ $idx }}][code]" type="text" value="{{ old('coupons.'.$idx.'.code', $c['code'] ?? '') }}" placeholder="e.g. EARLY2026" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md uppercase outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 font-mono tracking-wide"/>
</div>
<div class="sm:col-span-4 min-w-0">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Discount type</label>
<select name="coupons[{{ $idx }}][discount_type]" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15">
@php $dt = old('coupons.'.$idx.'.discount_type', $c['discount_type'] ?? 'percent'); @endphp
<option value="percent" @selected($dt === 'percent')>Percent off</option>
<option value="fixed" @selected($dt === 'fixed')>Fixed amount (USD)</option>
</select>
</div>
<div class="sm:col-span-4 min-w-0">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Value</label>
<input name="coupons[{{ $idx }}][discount_value]" type="number" step="0.01" min="0" value="{{ old('coupons.'.$idx.'.discount_value', $c['discount_value'] ?? '') }}" placeholder="10" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 tabular-nums"/>
</div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4 sm:items-end">
<div class="sm:col-span-4 min-w-0">
<label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Max uses <span class="font-normal normal-case text-on-surface-variant/80">(optional)</span></label>
<input name="coupons[{{ $idx }}][max_uses]" type="number" min="0" value="{{ old('coupons.'.$idx.'.max_uses', $c['max_uses'] ?? '') }}" placeholder="Unlimited" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 tabular-nums"/>
</div>
<div class="sm:col-span-4 min-w-0">
<label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Valid from</label>
<input name="coupons[{{ $idx }}][valid_from]" type="date" value="{{ old('coupons.'.$idx.'.valid_from', $c['valid_from'] ?? '') }}" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-body-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
</div>
<div class="sm:col-span-4 min-w-0">
<label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Valid until</label>
<input name="coupons[{{ $idx }}][valid_until]" type="date" value="{{ old('coupons.'.$idx.'.valid_until', $c['valid_until'] ?? '') }}" class="w-full rounded-xl border border-outline-variant bg-white px-4 py-2.5 text-body-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15"/>
</div>
</div>
<div class="flex items-center gap-3 pt-1">
<input type="hidden" name="coupons[{{ $idx }}][is_active]" value="0"/>
<label class="inline-flex items-center gap-2 cursor-pointer select-none">
@php
    $isActive = filter_var(old('coupons.'.$idx.'.is_active', $c['is_active'] ?? true), FILTER_VALIDATE_BOOLEAN);
@endphp
<input name="coupons[{{ $idx }}][is_active]" type="checkbox" value="1" class="rounded border-outline-variant text-primary focus:ring-primary/25" @checked($isActive)/>
<span class="text-body-sm text-on-surface font-medium">Active</span>
</label>
</div>
</div>
</div>
