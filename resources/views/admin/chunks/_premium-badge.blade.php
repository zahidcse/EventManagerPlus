@php
  $editionPremiumMessage = $editionPremiumMessage ?? \App\Support\Edition::premiumMessage();
  $editionPremiumUrl = $editionPremiumUrl ?? \App\Support\Edition::premiumUrl();
  $size = $size ?? 'sm';
  $class = $size === 'md'
    ? 'inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-body-sm font-semibold text-amber-900'
    : 'inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-amber-900';
@endphp
<a href="{{ $editionPremiumUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $class }} hover:bg-amber-100 transition-colors">
  <span class="material-symbols-outlined text-[14px]">lock</span>
  {{ $editionPremiumMessage }}
</a>
