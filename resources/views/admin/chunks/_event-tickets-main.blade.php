@php
    $ticketRows = old('tickets');
    if (! is_array($ticketRows)) {
        $saved = $event->ticketRows();
        if (is_array($saved) && count($saved) > 0) {
            $ticketRows = array_values($saved);
        } else {
            $ticketRows = [['name' => '', 'price' => '', 'quantity' => '', 'sales_start' => '', 'sales_end' => '', 'early_bird_price' => '', 'early_bird_ends_at' => '']];
        }
    } else {
        $ticketRows = array_values($ticketRows);
    }
    $serviceRows = old('additional_services');
    if (! is_array($serviceRows)) {
        $serviceRows = $event->additionalServiceRows();
    } else {
        $serviceRows = array_values($serviceRows);
        if (count($serviceRows) === 0) {
            $serviceRows = [['name' => '', 'price' => '', 'quantity' => '']];
        }
    }
    $couponRows = old('coupons');
    if (! is_array($couponRows)) {
        $couponRows = $event->couponRows();
    } else {
        $couponRows = array_values($couponRows);
        if (count($couponRows) === 0) {
            $couponRows = [[
                'id' => '',
                'code' => '',
                'discount_type' => 'percent',
                'discount_value' => '',
                'max_uses' => '',
                'valid_from' => '',
                'valid_until' => '',
                'is_active' => true,
            ]];
        }
    }
@endphp
<form method="post" action="{{ route('admin.events.update.tickets', $event) }}" id="event-tickets-form">
@csrf
@method('PUT')
<div class="p-8 max-w-7xl mx-auto pb-36">
@if(session('success'))
<div class="mb-6 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md flex items-center gap-2">
<span class="material-symbols-outlined text-primary">check_circle</span>
{{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="mb-6 rounded-xl border border-error/40 bg-error/5 px-4 py-3 text-body-md text-on-surface">
<p class="font-semibold text-error mb-2">Could not save — please check the fields below.</p>
<ul class="list-disc list-inside space-y-1 text-sm">
@foreach($errors->all() as $err)
<li>{{ $err }}</li>
@endforeach
</ul>
</div>
@endif
@include('admin.chunks._event-wizard-steps', ['currentStep' => 2, 'event' => $event])
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
<div class="lg:col-span-8 space-y-4">
<section class="rounded-2xl border border-outline-variant bg-white shadow-sm overflow-hidden">
<div class="px-5 py-3 border-b border-outline-variant bg-surface-container-low/50">
<div>
<h2 class="text-base font-bold text-on-surface leading-tight">Ticket types</h2>
</div>
</div>
<div class="p-4 space-y-4">
<div class="rounded-xl border border-outline-variant/80 bg-surface-container-low/40 p-3.5">
<div class="flex items-start gap-3">
@include('admin.chunks._toggle-switch', [
  'name' => 'global_ticket_quantity_enabled',
  'id' => 'global-ticket-qty-enabled',
  'checked' => old('global_ticket_quantity_enabled', $event->global_ticket_quantity_enabled ?? false),
  'includeHidden' => true,
])
<label for="global-ticket-qty-enabled" class="min-w-0 cursor-pointer select-none">
<span class="block text-sm font-semibold text-on-surface leading-snug">Shared quantity for all ticket types</span>
</label>
</div>
<div id="global-ticket-qty-fields" class="mt-3 @if(! old('global_ticket_quantity_enabled', $event->global_ticket_quantity_enabled ?? false)) hidden @endif">
<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
<label for="global-ticket-qty-value" class="text-label-md font-semibold text-on-surface shrink-0">Total tickets available</label>
<input type="number" name="global_ticket_quantity" id="global-ticket-qty-value" min="0" value="{{ old('global_ticket_quantity', $event->global_ticket_quantity ?? 0) }}" class="w-full sm:w-28 shrink-0 rounded-xl border border-outline-variant bg-white px-4 py-3 text-body-md outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 tabular-nums"/>
</div>
@error('global_ticket_quantity')
<p class="text-error text-sm mt-2">{{ $message }}</p>
@enderror
</div>
</div>
<div id="ticket-tiers-container" class="space-y-4">
@foreach($ticketRows as $idx => $t)
@include('admin.chunks._ticket-tier-row', ['idx' => $idx, 't' => $t, 'showRemove' => count($ticketRows) > 1])
@endforeach
</div>
<button type="button" id="ticket-add-tier" class="admin-list-add-btn">
<span class="admin-list-add-btn__icon material-symbols-outlined" aria-hidden="true">add</span>
<span>Add new ticket type</span>
</button>
@error('tickets')
<p class="text-error text-sm mt-3">{{ $message }}</p>
@enderror
</div>
</section>
<section class="rounded-2xl border border-outline-variant bg-white shadow-sm overflow-hidden relative">
<div class="px-5 py-3 border-b border-outline-variant bg-surface-container-low/50 flex items-center justify-between gap-3">
<div>
<h2 class="text-base font-bold text-on-surface leading-tight">Additional services</h2>
</div>
@if($editionIsFree ?? false)
@include('admin.chunks._premium-badge')
@endif
</div>
<div class="p-4 {{ ($editionIsFree ?? false) ? 'opacity-60 pointer-events-none select-none' : '' }}">
<div id="additional-services-container" class="space-y-4">
@foreach($serviceRows as $sidx => $s)
@include('admin.chunks._additional-service-row', ['idx' => $sidx, 's' => $s, 'showRemove' => count($serviceRows) > 1])
@endforeach
</div>
@if(! ($editionIsFree ?? false))
<button type="button" id="additional-service-add" class="admin-list-add-btn">
<span class="admin-list-add-btn__icon material-symbols-outlined" aria-hidden="true">add</span>
<span>Add new service</span>
</button>
@endif
@error('additional_services')
<p class="text-error text-sm mt-3">{{ $message }}</p>
@enderror
</div>
</section>
<section class="rounded-2xl border border-outline-variant bg-white shadow-sm overflow-hidden">
<div class="px-5 py-3 border-b border-outline-variant bg-surface-container-low/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
<div>
<h2 class="text-base font-bold text-on-surface leading-tight">Coupon codes</h2>
</div>
<button type="button" id="coupon-add-row" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 border-primary/35 text-primary font-semibold text-label-md hover:bg-primary/5 transition-all active:scale-[0.98] shrink-0">
<span class="material-symbols-outlined text-[20px]">sell</span>
Add coupon
</button>
</div>
<div class="p-4">
<div id="coupons-container" class="space-y-4">
@foreach($couponRows as $cidx => $c)
@include('admin.chunks._coupon-row', ['idx' => $cidx, 'c' => $c, 'showRemove' => count($couponRows) > 1])
@endforeach
</div>
@error('coupons')
<p class="text-error text-sm mt-3">{{ $message }}</p>
@enderror
</div>
</section>
</div>
<aside class="lg:col-span-4 lg:sticky lg:top-24 space-y-6">
<section class="rounded-2xl bg-gradient-to-br from-primary to-primary-container text-on-primary p-6 shadow-lg shadow-primary/20 relative overflow-hidden">
<div class="absolute -right-16 -bottom-16 w-44 h-44 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
<div class="relative z-10">
<div class="flex items-center gap-2 mb-5">
<span class="material-symbols-outlined text-2xl opacity-90">analytics</span>
<h3 class="text-lg font-bold">Live summary</h3>
</div>
<div class="space-y-4">
<div class="flex justify-between items-baseline gap-4 py-3 border-b border-white/20">
<span class="text-body-sm text-on-primary/85">Total capacity</span>
<span id="ticket-sum-capacity" class="text-2xl font-bold tabular-nums">0</span>
</div>
<div class="flex justify-between items-baseline gap-4 py-3 border-b border-white/20">
<span class="text-body-sm text-on-primary/85">Est. revenue</span>
<span id="ticket-sum-revenue" class="text-xl font-bold tabular-nums">$0</span>
</div>
<div class="flex justify-between items-baseline gap-4 py-3">
<span class="text-body-sm text-on-primary/85">Filled tiers</span>
<span id="ticket-sum-tiers" class="text-xl font-bold tabular-nums">0</span>
</div>
</div>
</div>
</section>
</aside>
</div>
</div>
<template id="ticket-tier-template">
@include('admin.chunks._ticket-tier-row', ['idx' => '__INDEX__', 't' => ['name' => '', 'price' => '', 'quantity' => '', 'sales_start' => '', 'sales_end' => '', 'early_bird_price' => '', 'early_bird_ends_at' => ''], 'showRemove' => true])
</template>
<template id="additional-service-template">
@include('admin.chunks._additional-service-row', ['idx' => '__INDEX__', 's' => ['name' => '', 'price' => '', 'quantity' => ''], 'showRemove' => true])
</template>
<template id="coupon-row-template">
@include('admin.chunks._coupon-row', ['idx' => '__INDEX__', 'c' => ['id' => '', 'code' => '', 'discount_type' => 'percent', 'discount_value' => '', 'max_uses' => '', 'valid_from' => '', 'valid_until' => '', 'is_active' => true], 'showRemove' => true])
</template>
<footer class="fixed bottom-0 right-0 left-sidebar-width h-20 bg-white/95 backdrop-blur-sm border-t border-outline-variant z-40 px-6 sm:px-8 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
<a href="{{ route('admin.events.edit', $event) }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-secondary font-semibold hover:bg-surface-container-low rounded-xl transition-colors order-2 sm:order-1 shrink-0">
<span class="material-symbols-outlined text-[20px]">arrow_back</span>
Previous step
</a>
<div class="flex items-center justify-end gap-3 order-1 sm:order-2 flex-wrap">
@include('admin.chunks._event-wizard-seat-plan-link')
@include('admin.chunks._event-wizard-save-button', ['currentStep' => 2, 'formId' => 'event-tickets-form'])
<button type="submit" form="event-tickets-form" name="wizard_action" value="continue" class="px-6 py-2.5 bg-primary text-white font-bold rounded-xl hover:bg-primary-container transition-all shadow-md shadow-primary/25 active:scale-[0.98] inline-flex items-center gap-2">
Continue to content
<span class="material-symbols-outlined text-[18px]">arrow_forward</span>
</button>
</div>
</footer>
</form>
<script>
(function () {
  var container = document.getElementById('ticket-tiers-container');
  var template = document.getElementById('ticket-tier-template');
  var addBtn = document.getElementById('ticket-add-tier');
  if (!container || !template || !addBtn) return;

  function getNextIndex() {
    var max = -1;
    container.querySelectorAll('.ticket-tier-row').forEach(function (row) {
      var n = row.querySelector('[name^="tickets["]');
      if (!n || !n.name) return;
      var m = n.name.match(/^tickets\[(\d+)\]/);
      if (m) max = Math.max(max, parseInt(m[1], 10));
    });
    return max + 1;
  }

  function renumberBadges() {
    var rows = container.querySelectorAll('.ticket-tier-row');
    rows.forEach(function (row, i) {
      var badge = row.querySelector('.ticket-tier-badge');
      if (badge) badge.textContent = String(i + 1);
    });
  }

  function toggleRemoveButtons() {
    var rows = container.querySelectorAll('.ticket-tier-row');
    var mult = rows.length > 1;
    rows.forEach(function (row) {
      var btn = row.querySelector('.ticket-remove-btn');
      if (!btn) return;
      btn.classList.toggle('hidden', !mult);
      btn.disabled = !mult;
    });
  }

  function recalcSummary() {
    var globalCb = document.getElementById('global-ticket-qty-enabled');
    var globalInp = document.getElementById('global-ticket-qty-value');
    var useGlobal = globalCb && globalCb.checked;
    var cap = 0, rev = 0, tiers = 0;
    if (useGlobal) {
      cap = parseInt(globalInp && globalInp.value, 10) || 0;
    }
    container.querySelectorAll('.ticket-tier-row').forEach(function (row) {
      var nameIn = row.querySelector('.ticket-field-name');
      var name = nameIn ? nameIn.value.trim() : '';
      if (!name) return;
      tiers++;
      var p = parseFloat(row.querySelector('.ticket-field-price') && row.querySelector('.ticket-field-price').value) || 0;
      if (!useGlobal) {
        var q = parseInt(row.querySelector('.ticket-field-qty') && row.querySelector('.ticket-field-qty').value, 10) || 0;
        cap += q;
        rev += p * q;
      }
    });
    var elC = document.getElementById('ticket-sum-capacity');
    var elR = document.getElementById('ticket-sum-revenue');
    var elT = document.getElementById('ticket-sum-tiers');
    if (elC) elC.textContent = cap.toLocaleString();
    if (elR) {
      if (useGlobal && tiers > 0) {
        elR.textContent = '—';
      } else {
        elR.textContent = '$' + Math.round(rev).toLocaleString();
      }
    }
    if (elT) elT.textContent = String(tiers);
  }

  function syncGlobalMode() {
    var globalCb = document.getElementById('global-ticket-qty-enabled');
    var globalFields = document.getElementById('global-ticket-qty-fields');
    var useGlobal = globalCb && globalCb.checked;
    if (globalFields) globalFields.classList.toggle('hidden', !useGlobal);
    container.querySelectorAll('.ticket-per-tier-qty-wrap').forEach(function (el) {
      el.classList.toggle('opacity-45', useGlobal);
      el.classList.toggle('pointer-events-none', useGlobal);
      var inp = el.querySelector('.ticket-field-qty');
      if (inp) {
        inp.disabled = !!useGlobal;
      }
    });
    recalcSummary();
  }

  function bindRow(row) {
    row.querySelectorAll('input').forEach(function (inp) {
      inp.addEventListener('input', recalcSummary);
      inp.addEventListener('change', recalcSummary);
    });
    var rm = row.querySelector('.ticket-remove-btn');
    if (rm) rm.addEventListener('click', function () {
      if (container.querySelectorAll('.ticket-tier-row').length <= 1) return;
      row.remove();
      renumberBadges();
      toggleRemoveButtons();
      recalcSummary();
    });
  }

  container.querySelectorAll('.ticket-tier-row').forEach(bindRow);

  addBtn.addEventListener('click', function () {
    var idx = getNextIndex();
    var html = template.innerHTML.replace(/__INDEX__/g, String(idx));
    var wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    var row = wrap.firstElementChild;
    if (!row) return;
    container.appendChild(row);
    renumberBadges();
    toggleRemoveButtons();
    bindRow(row);
    syncGlobalMode();
    if (window.adminFocusNewRow) window.adminFocusNewRow(row, '.ticket-field-name');
  });

  var globalCb = document.getElementById('global-ticket-qty-enabled');
  var globalInp = document.getElementById('global-ticket-qty-value');
  if (globalCb) {
    globalCb.addEventListener('change', syncGlobalMode);
  }
  if (globalInp) {
    globalInp.addEventListener('input', recalcSummary);
    globalInp.addEventListener('change', recalcSummary);
  }

  renumberBadges();
  toggleRemoveButtons();
  syncGlobalMode();
})();

(function () {
  var container = document.getElementById('additional-services-container');
  var template = document.getElementById('additional-service-template');
  var addBtn = document.getElementById('additional-service-add');
  if (!container || !template || !addBtn) return;

  function getNextIndex() {
    var max = -1;
    container.querySelectorAll('.additional-service-row').forEach(function (row) {
      var n = row.querySelector('[name^="additional_services["]');
      if (!n || !n.name) return;
      var m = n.name.match(/^additional_services\[(\d+)\]/);
      if (m) max = Math.max(max, parseInt(m[1], 10));
    });
    return max + 1;
  }

  function renumberBadges() {
    var rows = container.querySelectorAll('.additional-service-row');
    rows.forEach(function (row, i) {
      var badge = row.querySelector('.additional-service-badge');
      if (badge) badge.textContent = String(i + 1);
    });
  }

  function toggleRemoveButtons() {
    var rows = container.querySelectorAll('.additional-service-row');
    var mult = rows.length > 1;
    rows.forEach(function (row) {
      var btn = row.querySelector('.additional-service-remove-btn');
      if (!btn) return;
      btn.classList.toggle('hidden', !mult);
      btn.disabled = !mult;
    });
  }

  function bindRow(row) {
    var rm = row.querySelector('.additional-service-remove-btn');
    if (rm) rm.addEventListener('click', function () {
      if (container.querySelectorAll('.additional-service-row').length <= 1) return;
      row.remove();
      renumberBadges();
      toggleRemoveButtons();
    });
  }

  container.querySelectorAll('.additional-service-row').forEach(bindRow);

  addBtn.addEventListener('click', function () {
    var idx = getNextIndex();
    var html = template.innerHTML.replace(/__INDEX__/g, String(idx));
    var wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    var row = wrap.firstElementChild;
    if (!row) return;
    container.appendChild(row);
    renumberBadges();
    toggleRemoveButtons();
    bindRow(row);
    if (window.adminFocusNewRow) window.adminFocusNewRow(row, '.additional-service-field-name');
  });

  renumberBadges();
  toggleRemoveButtons();
})();

(function () {
  var container = document.getElementById('coupons-container');
  var template = document.getElementById('coupon-row-template');
  var addBtn = document.getElementById('coupon-add-row');
  if (!container || !template || !addBtn) return;

  function getNextIndex() {
    var max = -1;
    container.querySelectorAll('.coupon-row').forEach(function (row) {
      var n = row.querySelector('[name^="coupons["]');
      if (!n || !n.name) return;
      var m = n.name.match(/^coupons\[(\d+)\]/);
      if (m) max = Math.max(max, parseInt(m[1], 10));
    });
    return max + 1;
  }

  function renumberBadges() {
    var rows = container.querySelectorAll('.coupon-row');
    rows.forEach(function (row, i) {
      var badge = row.querySelector('.coupon-row-badge');
      if (badge) badge.textContent = String(i + 1);
    });
  }

  function toggleRemoveButtons() {
    var rows = container.querySelectorAll('.coupon-row');
    var mult = rows.length > 1;
    rows.forEach(function (row) {
      var btn = row.querySelector('.coupon-remove-btn');
      if (!btn) return;
      btn.classList.toggle('hidden', !mult);
      btn.disabled = !mult;
    });
  }

  function bindRow(row) {
    var rm = row.querySelector('.coupon-remove-btn');
    if (rm) rm.addEventListener('click', function () {
      if (container.querySelectorAll('.coupon-row').length <= 1) return;
      row.remove();
      renumberBadges();
      toggleRemoveButtons();
    });
  }

  container.querySelectorAll('.coupon-row').forEach(bindRow);

  addBtn.addEventListener('click', function () {
    var idx = getNextIndex();
    var html = template.innerHTML.replace(/__INDEX__/g, String(idx));
    var wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    var row = wrap.firstElementChild;
    if (!row) return;
    container.prepend(row);
    renumberBadges();
    toggleRemoveButtons();
    bindRow(row);
    if (window.adminFocusNewRow) window.adminFocusNewRow(row, 'input[name$="[code]"]');
  });

  renumberBadges();
  toggleRemoveButtons();
})();
</script>
