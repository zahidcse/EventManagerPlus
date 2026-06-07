<details class="admin-booking-notifications relative" data-unread-count="{{ (int) $adminBookingNotificationCount }}">
<summary class="list-none cursor-pointer flex items-center justify-center text-on-surface-variant hover:text-primary hover:bg-black/5 dark:hover:bg-white/10 p-2 rounded-full transition-colors relative [&::-webkit-details-marker]:hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/25">
<span class="material-symbols-outlined leading-none">notifications</span>
@if($adminBookingNotificationCount > 0)
<span class="admin-booking-notif-badge absolute top-1 right-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-error text-white text-[10px] font-bold leading-none border-2 border-white dark:border-[#2b2930]" aria-live="polite">{{ $adminBookingNotificationCount > 99 ? '99+' : $adminBookingNotificationCount }}</span>
@endif
</summary>
<div class="absolute right-0 top-[calc(100%+10px)] w-[min(100vw-2rem,20rem)] rounded-xl bg-surface-container-lowest dark:bg-[#2b2930] border border-outline-variant shadow-xl z-[60] overflow-hidden" role="menu">
<div class="px-4 py-3 border-b border-outline-variant bg-surface-container-low/50 dark:bg-white/5">
<p class="text-sm font-semibold text-on-surface">Booking alerts</p>
<p class="text-xs text-on-surface-variant mt-0.5">Open a row to mark it as read (stays on this page).</p>
</div>
@if($adminBookingNotificationItems->isEmpty())
<div class="admin-booking-notif-empty px-4 py-8 text-center text-sm text-on-surface-variant">You are all caught up.</div>
@else
<div class="admin-booking-notif-list admin-sidebar-scroll max-h-72 overflow-y-auto overscroll-y-contain divide-y divide-outline-variant/80">
@foreach($adminBookingNotificationItems as $booking)
@php($ev = $booking->event)
<button type="button" role="menuitem" class="admin-booking-notif-dismiss block w-full px-4 py-3 text-left hover:bg-surface-container-low dark:hover:bg-white/5 transition-colors focus:outline-none focus-visible:bg-surface-container-low cursor-pointer bg-transparent border-0 font-sans text-inherit disabled:opacity-50 disabled:pointer-events-none" data-dismiss-url="{{ route('admin.notifications.bookings.dismiss', $booking) }}">
<p class="text-sm font-semibold text-on-surface truncate">{{ $ev?->title ?? 'Event' }}</p>
<p class="text-xs text-on-surface-variant mt-1 truncate">{{ $booking->attendee_name }}@if(filled($booking->email)) · {{ $booking->email }}@endif</p>
<p class="text-[11px] text-on-surface-variant/80 mt-1">{{ $booking->created_at->diffForHumans() }} · <span class="capitalize">{{ str_replace('_', ' ', $booking->status) }}</span></p>
</button>
@endforeach
</div>
@endif
</div>
</details>
@once
@push('scripts')
<script>
(function () {
  function csrfToken() {
    var el = document.querySelector('meta[name="csrf-token"]');
    return el ? el.getAttribute('content') : '';
  }

  function badgeLabel(n) {
    if (n <= 0) return '';
    return n > 99 ? '99+' : String(n);
  }

  function syncBadge(detailsRoot) {
    var n = parseInt(detailsRoot.getAttribute('data-unread-count'), 10);
    if (isNaN(n) || n < 0) n = 0;
    var summary = detailsRoot.querySelector('summary');
    if (!summary) return;
    var badge = summary.querySelector('.admin-booking-notif-badge');
    if (n <= 0) {
      if (badge) badge.remove();
      return;
    }
    var label = badgeLabel(n);
    if (badge) {
      badge.textContent = label;
    } else {
      var span = document.createElement('span');
      span.className = 'admin-booking-notif-badge absolute top-1 right-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-error text-white text-[10px] font-bold leading-none border-2 border-white dark:border-[#2b2930]';
      span.setAttribute('aria-live', 'polite');
      span.textContent = label;
      summary.appendChild(span);
    }
  }

  document.body.addEventListener('click', function (e) {
    var btn = e.target.closest('button.admin-booking-notif-dismiss');
    if (!btn || btn.disabled) return;

    var dismissUrl = btn.getAttribute('data-dismiss-url');
    if (!dismissUrl) return;

    e.preventDefault();
    e.stopPropagation();

    var detailsRoot = btn.closest('details.admin-booking-notifications');
    var listEl = btn.closest('.admin-booking-notif-list');

    btn.disabled = true;

    fetch(dismissUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken()
      },
      credentials: 'same-origin',
      body: '{}'
    })
      .then(function (r) {
        if (!r.ok) throw new Error('dismiss failed');
        return r.json();
      })
      .then(function (data) {
        if (!data || !data.ok) throw new Error('bad payload');

        btn.remove();

        if (detailsRoot) {
          var prev = parseInt(detailsRoot.getAttribute('data-unread-count'), 10);
          if (isNaN(prev)) prev = 0;
          detailsRoot.setAttribute('data-unread-count', String(Math.max(0, prev - 1)));
          syncBadge(detailsRoot);
        }

        if (listEl && !listEl.querySelector('.admin-booking-notif-dismiss')) {
          listEl.classList.remove('admin-sidebar-scroll', 'max-h-72', 'overflow-y-auto', 'overscroll-y-contain', 'divide-y', 'divide-outline-variant/80');
          listEl.innerHTML = '<div class="admin-booking-notif-empty px-4 py-8 text-center text-sm text-on-surface-variant">You are all caught up.</div>';
        }
      })
      .catch(function () {
        btn.disabled = false;
      });
  });
})();
</script>
@endpush
@endonce
