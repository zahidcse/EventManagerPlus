<!DOCTYPE html>
<html class="light" lang="en">
<head>
@include('admin.partials.head-inner')
<title>@yield('title', 'Admin') | Event Manager</title>
@stack('styles')
</head>
<body class="flex min-h-screen text-on-surface">
@include('admin.partials.sidebar')
<div class="flex-1 ml-sidebar-width min-w-0 flex flex-col min-h-screen bg-surface admin-content-column">
@if($editionIsFree ?? false)
<div class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-amber-200 bg-amber-50 px-6 py-2.5 text-body-sm text-amber-950">
<span class="inline-flex items-center gap-2 min-w-0">
<span class="material-symbols-outlined text-[18px] shrink-0">info</span>
<span><strong>Event Manager Free</strong> — some settings are preview-only. Upgrade for the full feature set.</span>
</span>
<a href="{{ $editionPremiumUrl ?? \App\Support\Edition::premiumUrl() }}" target="_blank" rel="noopener noreferrer" class="shrink-0 font-semibold text-amber-900 hover:underline whitespace-nowrap">Get Event Manager Plus</a>
</div>
@endif
@yield('content')
</div>
<script src="{{ asset('js/searchable-select.js') }}" defer></script>
<script>
window.adminFocusNewRow = function (row, selector) {
  if (!row) return;
  var el = selector ? row.querySelector(selector) : null;
  if (!el) {
    el = row.querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled])');
  }
  if (!el) return;
  window.setTimeout(function () {
    row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    try {
      el.focus({ preventScroll: true });
    } catch (e) {
      el.focus();
    }
    if (typeof el.select === 'function' && (el.type === 'text' || el.type === 'search' || el.tagName === 'TEXTAREA')) {
      el.select();
    }
  }, 0);
};
</script>
@stack('scripts')
</body>
</html>
