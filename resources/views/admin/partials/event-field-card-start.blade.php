@php
    $cardTitle = $cardTitle ?? '';
    $cardAddNew = $cardAddNew ?? null;
    $cardShowAddNew = $cardShowAddNew ?? false;
@endphp
<div class="rounded-xl border border-outline-variant overflow-visible bg-white shadow-sm">
<div class="flex items-center justify-between gap-3 px-4 py-3 bg-surface-container-low border-b border-outline-variant">
<span class="text-label-md font-bold text-outline uppercase tracking-wider">{{ $cardTitle }}</span>
@if($cardAddNew && $cardShowAddNew)
<button type="button" class="text-label-md font-semibold text-primary shrink-0 hover:underline" data-quick-open-modal="{{ $cardAddNew }}">Add new</button>
@endif
</div>
<div class="p-4 space-y-3">
