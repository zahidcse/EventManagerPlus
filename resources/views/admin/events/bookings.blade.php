@extends('admin.layouts.app')

@section('title', 'Bookings')

@section('content')
  @include('admin.partials.admin-topbar')
  <main class="mt-16 p-8 min-h-screen">
    <div class="max-w-7xl mx-auto w-full flex flex-col gap-6">
      <nav class="flex text-label-md text-on-surface-variant gap-2 flex-wrap">
        <a class="hover:text-primary transition-colors" href="{{ route('admin.events.index') }}">Events</a>
        <span>/</span>
        <a class="hover:text-primary transition-colors truncate max-w-[min(40vw,14rem)]"
          href="{{ route('admin.events.edit', $event) }}">{{ $event->title }}</a>
        <span>/</span>
        <span class="text-on-surface font-medium">Bookings</span>
      </nav>
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-on-surface tracking-tight">Bookings &amp; attendees</h1>
          <p class="text-body-md text-on-surface-variant mt-1">{{ $event->title }}</p>
        </div>
        <a href="{{ route('admin.events.edit', $event) }}"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-outline-variant text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors shrink-0">
          <span class="material-symbols-outlined text-[20px]">edit</span>
          Edit event
        </a>
      </div>
      <div id="admin-event-bookings-page"
        class="rounded-xl border border-outline-variant bg-surface-container-lowest shadow-sm min-h-[12rem]"
        data-bookings-url="{{ $bookingsDataUrl }}" data-initial-q="{{ $initialSearch }}">
        <p class="text-sm text-on-surface-variant px-6 py-8 text-center">Loading bookings…</p>
      </div>
    </div>
  </main>

  <div id="edit-booking-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-black/50 transition-opacity"></div>
    <div class="flex min-h-screen items-center justify-center p-4">
      <div
        class="relative w-full max-w-lg rounded-xl bg-surface-container-lowest shadow-2xl border border-outline-variant">
        <div class="flex items-center justify-between p-6 border-b border-outline-variant">
          <h3 class="text-lg font-bold text-on-surface">Edit Booking</h3>
          <button type="button" class="close-modal text-on-surface-variant hover:text-on-surface">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
        <form id="edit-booking-form" class="p-6 space-y-4">
          @csrf
          <input type="hidden" name="_method" value="PUT">
          <input type="hidden" id="edit-order-group-id">

          <div class="space-y-1">
            <label class="text-sm font-semibold text-on-surface-variant">Attendee Name</label>
            <input type="text" name="attendee_name" id="edit-attendee-name"
              class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-inherit"
              required>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="text-sm font-semibold text-on-surface-variant">Email</label>
              <input type="email" name="email" id="edit-email"
                class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-inherit">
            </div>
            <div class="space-y-1">
              <label class="text-sm font-semibold text-on-surface-variant">Phone</label>
              <input type="text" name="phone" id="edit-phone"
                class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-inherit">
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="text-sm font-semibold text-on-surface-variant">Status</label>
              <select name="status" id="edit-status"
                class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-inherit">
                <option value="pending">Pending</option>
                <option value="pending_offline_payment">Pending Offline Payment</option>
                <option value="confirmed">Confirmed</option>
                <option value="cancelled">Cancelled</option>
                <option value="checked_in">Checked In</option>
              </select>
            </div>
            <div class="flex items-center gap-2 pt-6">
              <input type="checkbox" name="is_checked_in" id="edit-is-checked-in" value="1"
                class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary">
              <label for="edit-is-checked-in" class="text-sm font-semibold text-on-surface-variant">Mark all as
                checked-in</label>
            </div>
          </div>

          <div class="space-y-1">
            <label class="text-sm font-semibold text-on-surface-variant">Staff Notes</label>
            <textarea name="notes" id="edit-notes" rows="3"
              class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-inherit"></textarea>
          </div>

          <div id="edit-group-items-section" class="hidden space-y-2 pt-2 border-t border-outline-variant">
            <label class="text-[11px] font-bold uppercase tracking-wider text-outline">Group Attendees</label>
            <div id="edit-group-items-list"
              class="divide-y divide-outline-variant border border-outline-variant rounded-lg overflow-hidden bg-white/50">
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button type="button"
              class="close-modal px-4 py-2 rounded-lg border border-outline-variant text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">Cancel</button>
            <button type="submit"
              class="px-4 py-2 rounded-lg bg-primary text-on-primary text-sm font-semibold hover:bg-primary/90 transition-colors shadow-sm">Save
              Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div id="group-details-modal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
    <div class="fixed inset-0 bg-black/60 transition-opacity close-group-modal"></div>
    <div class="flex min-h-screen items-center justify-center p-4">
      <div
        class="relative w-full max-w-3xl rounded-2xl bg-surface-container-lowest shadow-2xl border border-outline-variant overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b border-outline-variant bg-surface-container-low/30">
          <div>
            <h3 class="text-xl font-bold text-on-surface">Group Order Details</h3>
            <p class="text-sm text-on-surface-variant mt-0.5" id="group-modal-subtitle"></p>
          </div>
          <button type="button"
            class="close-group-modal w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant transition-colors">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
        <div class="p-6 max-h-[70vh] overflow-y-auto">
          <div id="group-details-content" class="space-y-4">
            <!-- Content injected via JS -->
          </div>
        </div>
        <div class="p-4 bg-surface-container-low/30 border-t border-outline-variant flex justify-end">
          <button type="button"
            class="close-group-modal px-6 py-2 rounded-xl bg-on-surface text-surface text-sm font-bold hover:bg-on-surface-variant transition-colors">Close</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    (function () {
      function escapeHtml(s) {
        if (s == null || s === '') return '';
        return String(s)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;');
      }

      var shell = document.getElementById('admin-event-bookings-page');
      if (!shell) return;

      var dataUrl = shell.getAttribute('data-bookings-url');
      var currentQ = shell.getAttribute('data-initial-q') || '';
      var searchTimer;

      function fetchUrl(fullUrl) {
        shell.innerHTML = '<p class="text-sm text-on-surface-variant px-6 py-8 text-center">Loading bookings…</p>';
        fetch(fullUrl, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        })
          .then(function (r) {
            if (!r.ok) throw new Error('Request failed');
            return r.json();
          })
          .then(render)
          .catch(function () {
            shell.innerHTML = '<p class="text-error text-sm px-6 py-8 text-center">Could not load bookings.</p>';
          });
      }

      function render(data) {
        var bookings = data.bookings || [];
        var p = data.pagination || {};
        var html = '';

        html += '<div class="p-4 sm:p-6">';
        html += '<div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between mb-4">';
        html += '<div class="flex items-center gap-2 flex-1 min-w-0">';
        html += '<span class="material-symbols-outlined text-outline text-[20px] shrink-0">search</span>';
        html += '<input type="search" class="admin-event-bookings-search flex-1 min-w-0 bg-white dark:bg-[#36343b] border border-outline-variant rounded-lg px-3 py-2 text-[13px]" placeholder="Search name, email, phone…" value="' + escapeHtml(currentQ) + '"/>';
        html += '</div>';
        html += '<p class="text-[12px] text-on-surface-variant shrink-0">';
        if (!p.total) {
          html += 'No bookings';
        } else {
          html += 'Showing ' + p.from + '–' + p.to + ' of ' + p.total;
        }
        html += '</p></div>';

        if (!bookings.length) {
          html += '<p class="text-[13px] text-on-surface-variant py-6 text-center border border-dashed border-outline-variant rounded-lg bg-white/70 dark:bg-white/5">No bookings match this search.</p>';
        } else {
          html += '<div class="overflow-x-auto rounded-lg border border-outline-variant bg-white dark:bg-[#2b2930]">';
          html += '<table class="w-full text-left text-[13px]"><thead class="bg-surface-container-low border-b border-outline-variant"><tr>';
          html += '<th class="px-4 py-2 font-semibold text-outline">Name</th>';
          html += '<th class="px-4 py-2 font-semibold text-outline">Email</th>';
          html += '<th class="px-4 py-2 font-semibold text-outline">Phone</th>';
          html += '<th class="px-4 py-2 font-semibold text-outline">Ticket</th>';
          html += '<th class="px-4 py-2 font-semibold text-outline">Session</th>';
          html += '<th class="px-4 py-2 font-semibold text-outline">Payment ref.</th>';
          html += '<th class="px-4 py-2 font-semibold text-outline">Status</th>';
          html += '<th class="px-4 py-2 font-semibold text-outline">Check-in</th>';
          html += '<th class="px-4 py-2 font-semibold text-outline">Booked</th>';
          html += '<th class="px-4 py-2 font-semibold text-outline text-right">Actions</th>';
          html += '</tr></thead><tbody class="divide-y divide-outline-variant">';
          html += '<tr class="hover:bg-surface-container-lowest transition-colors whitespace-nowrap">';
          html += '<td class="px-4 py-3 font-medium text-on-surface">';
          if (b.items && b.items.length > 1) {
            html += '<button type="button" class="view-group-btn mr-2 text-primary hover:scale-110 transition-transform align-middle" data-gid="' + escapeHtml(b.order_group_id) + '" title="View group details"><span class="material-symbols-outlined text-[22px]">group</span></button>';
          }
          html += escapeHtml(b.attendee_name) + '</td>';
          html += '<td class="px-4 py-3 text-on-surface-variant">' + escapeHtml(b.email || '—') + '</td>';
          html += '<td class="px-4 py-3 text-on-surface-variant">' + escapeHtml(b.phone || '—') + '</td>';
          html += '<td class="px-4 py-3 text-on-surface-variant">' + escapeHtml(b.ticket_name || '—') + '</td>';
          html += '<td class="px-4 py-3 text-on-surface-variant">' + escapeHtml(b.occurrence_date_label || '—') + '</td>';
          var refCell = '—';
          if (b.offline_payment_reference) {
            refCell = (b.offline_payment_label ? b.offline_payment_label + ': ' : '') + b.offline_payment_reference;
          }
          html += '<td class="px-4 py-3 text-on-surface-variant text-[12px] max-w-[14rem] break-words">' + escapeHtml(refCell) + '</td>';
          html += '<td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-surface-container-high text-on-surface">' + escapeHtml(b.status || '') + '</span></td>';
          html += '<td class="px-4 py-3 text-on-surface-variant">' + escapeHtml(b.checked_in_at_label || '—') + '</td>';
          html += '<td class="px-4 py-3 text-on-surface-variant">' + escapeHtml(b.booked_at_label || '') + '</td>';
          html += '<td class="px-4 py-3 text-right">';
          html += '<button type="button" class="admin-event-bookings-edit text-primary hover:text-primary-subtle transition-colors p-1" data-order-group-id="' + escapeHtml(b.order_group_id) + '"><span class="material-symbols-outlined text-[20px]">edit</span></button>';
          html += '</td>';
          html += '</tr>';
        });
        html += '</tbody></table></div>';
      }

      if (p.last_page > 1) {
        html += '<div class="flex items-center justify-between gap-3 mt-4">';
        html += '<button type="button" class="admin-event-bookings-prev px-3 py-1.5 rounded-lg border border-outline-variant text-[13px] font-semibold text-on-surface hover:bg-surface-container-low disabled:opacity-40 disabled:cursor-not-allowed" ' + (data.prev_page_url ? '' : 'disabled') + '>Previous</button>';
        html += '<span class="text-[12px] text-on-surface-variant">Page ' + p.current_page + ' of ' + p.last_page + '</span>';
        html += '<button type="button" class="admin-event-bookings-next px-3 py-1.5 rounded-lg border border-outline-variant text-[13px] font-semibold text-on-surface hover:bg-surface-container-low disabled:opacity-40 disabled:cursor-not-allowed" ' + (data.next_page_url ? '' : 'disabled') + '>Next</button>';
        html += '</div>';
      }

      html += '</div>';
      shell.innerHTML = html;

      var searchEl = shell.querySelector('.admin-event-bookings-search');
      if (searchEl) {
        searchEl.addEventListener('input', function () {
          clearTimeout(searchTimer);
          searchTimer = setTimeout(function () {
            currentQ = searchEl.value;
            var u = new URL(dataUrl, window.location.origin);
            u.searchParams.set('page', '1');
            if (currentQ) u.searchParams.set('q', currentQ);
            else u.searchParams.delete('q');
            window.history.replaceState({}, '', '{{ url()->current() }}' + (u.searchParams.toString() ? '?' + u.searchParams.toString() : ''));
            fetchUrl(u.toString());
          }, 300);
        });
      }

      var prev = shell.querySelector('.admin-event-bookings-prev');
      var next = shell.querySelector('.admin-event-bookings-next');
      if (prev && data.prev_page_url) {
        prev.addEventListener('click', function () {
          fetchUrl(data.prev_page_url);
        });
      }
      if (next && data.next_page_url) {
        next.addEventListener('click', function () {
          fetchUrl(data.next_page_url);
        });
      }

      shell.querySelectorAll('.admin-event-bookings-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var gid = btn.getAttribute('data-order-group-id');
          openEditModal(gid);
        });
      });

      shell.querySelectorAll('.view-group-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          var gid = btn.getAttribute('data-gid');
          var booking = bookings.find(function (b) { return b.order_group_id === gid; });
          if (booking) openGroupModal(booking);
        });
      });
    }

          var groupModal = document.getElementById('group-details-modal');
    function openGroupModal(group) {
      if (!groupModal) return;
      var subtitle = document.getElementById('group-modal-subtitle');
      if (subtitle) subtitle.textContent = 'Order Reference: ' + group.order_group_id + ' • ' + (group.items ? group.items.length : 0) + ' Attendees';

      var content = document.getElementById('group-details-content');
      if (content) {
        var html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        (group.items || []).forEach(function (item) {
          html += '<div class="p-4 rounded-2xl border border-outline-variant bg-white shadow-sm flex flex-col gap-3">';
          html += '<div class="flex items-start justify-between gap-2">';
          html += '<div><p class="font-bold text-on-surface">' + escapeHtml(item.attendee_name) + '</p>';
          html += '<p class="text-[12px] text-on-surface-variant">' + escapeHtml(item.email || 'No email') + '</p></div>';
          html += '<span class="px-2 py-1 rounded-lg bg-surface-container-highest text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">' + escapeHtml(item.status) + '</span>';
          html += '</div>';
          html += '<div class="grid grid-cols-2 gap-2 text-[12px]">';
          html += '<div><span class="text-outline block text-[10px] font-bold uppercase">Ticket</span><span class="text-on-surface">' + escapeHtml(item.ticket_name) + '</span></div>';
          html += '<div><span class="text-outline block text-[10px] font-bold uppercase">Seat</span><span class="text-on-surface">' + escapeHtml(item.seat_label || '—') + '</span></div>';
          html += '<div><span class="text-outline block text-[10px] font-bold uppercase">Session</span><span class="text-on-surface">' + escapeHtml(item.occurrence_date_label || '—') + '</span></div>';
          html += '<div><span class="text-outline block text-[10px] font-bold uppercase">Phone</span><span class="text-on-surface">' + escapeHtml(item.phone || '—') + '</span></div>';
          html += '<div><span class="text-outline block text-[10px] font-bold uppercase">Check-in</span><span class="text-on-surface">' + escapeHtml(item.checked_in_at_label || '—') + '</span></div>';
          html += '</div>';
          if (item.notes) {
            html += '<div class="mt-1 pt-2 border-t border-dashed border-outline-variant">';
            html += '<span class="text-outline block text-[10px] font-bold uppercase mb-1">Staff Notes</span>';
            html += '<div class="text-[11px] text-on-surface-variant bg-surface-container-lowest p-2 rounded-lg italic">' + escapeHtml(item.notes) + '</div></div>';
          }
          html += '</div>';
        });
        html += '</div>';
        content.innerHTML = html;
      }

      groupModal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeGroupModal() {
      if (groupModal) groupModal.classList.add('hidden');
      if (!modal.classList.contains('hidden')) return; // Don't reset if edit modal is open
      document.body.style.overflow = '';
    }

    document.querySelectorAll('.close-group-modal').forEach(function (el) {
      el.addEventListener('click', closeGroupModal);
    });

    var modal = document.getElementById('edit-booking-modal');
    var editForm = document.getElementById('edit-booking-form');

    function openEditModal(gid) {
      if (!modal) return;
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';

      var detailsUrl = '{{ route("admin.events.bookings.details", [$event, ":gid"]) }}'.replace(':gid', gid);

      fetch(detailsUrl, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          document.getElementById('edit-order-group-id').value = gid;
          document.getElementById('edit-attendee-name').value = data.attendee_name || '';
          document.getElementById('edit-email').value = data.email || '';
          document.getElementById('edit-phone').value = data.phone || '';
          document.getElementById('edit-status').value = data.status || 'pending';
          document.getElementById('edit-is-checked-in').checked = !!data.is_checked_in;
          document.getElementById('edit-notes').value = data.notes || '';

          var groupSection = document.getElementById('edit-group-items-section');
          var groupList = document.getElementById('edit-group-items-list');
          if (groupSection && groupList) {
            if (data.items && data.items.length > 1) {
              groupSection.classList.remove('hidden');
              var itemsHtml = '';
              data.items.forEach(function (item) {
                itemsHtml += '<div class="px-3 py-2 text-[12px] flex justify-between items-center">';
                itemsHtml += '<div><span class="font-medium text-on-surface">' + escapeHtml(item.attendee_name) + '</span>';
                itemsHtml += '<br/><span class="text-[10px] text-on-surface-variant">' + escapeHtml(item.ticket_name);
                if (item.seat_label) itemsHtml += ' · Seat ' + escapeHtml(item.seat_label);
                itemsHtml += '</span></div>';
                itemsHtml += '<span class="text-[10px] px-1.5 py-0.5 rounded bg-surface-container-highest">' + escapeHtml(item.status) + '</span>';
                itemsHtml += '</div>';
              });
              groupList.innerHTML = itemsHtml;
            } else {
              groupSection.classList.add('hidden');
              groupList.innerHTML = '';
            }
          }
        });
    }

    function closeModal() {
      if (!modal) return;
      modal.classList.add('hidden');
      document.body.style.overflow = '';
      editForm.reset();
    }

    document.querySelectorAll('.close-modal').forEach(function (el) {
      el.addEventListener('click', closeModal);
    });

    if (editForm) {
      editForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var gid = document.getElementById('edit-order-group-id').value;
        var updateUrl = '{{ route("admin.events.bookings.update", [$event, ":gid"]) }}'.replace(':gid', gid);
        var formData = new FormData(editForm);

        // FormData doesn't include unchecked checkboxes
        if (!formData.has('is_checked_in')) {
          formData.append('is_checked_in', '0');
        }

        fetch(updateUrl, {
          method: 'POST', // We use POST with _method=PUT
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          }
        })
          .then(function (r) {
            if (!r.ok) return r.json().then(function (err) { throw err; });
            return r.json();
          })
          .then(function () {
            closeModal();
            // Refresh the list
            var u = new URL(dataUrl, window.location.origin);
            fetchUrl(u.toString());
          })
          .catch(function (err) {
            alert('Error: ' + (err.message || 'Something went wrong'));
          });
      });
    }

    var u = new URL(dataUrl, window.location.origin);
    u.searchParams.set('page', '1');
    if (currentQ) u.searchParams.set('q', currentQ);
    fetchUrl(u.toString());
        }) ();
  </script>
@endpush