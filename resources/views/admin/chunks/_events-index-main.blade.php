<div class="max-w-7xl mx-auto w-full flex flex-col gap-6">
@if(session('success'))
<div class="rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md">{{ session('success') }}</div>
@endif
<div class="flex justify-between items-end">
<div>
<h2 class="text-[28px] font-semibold tracking-tight text-on-surface">Manage Events</h2>
<p class="text-on-surface-variant text-[16px]">Overview and management of all scheduled enterprise events.</p>
</div>
<a href="{{ route('admin.events.create') }}" class="bg-primary text-white px-5 py-2.5 rounded-lg flex items-center gap-2 font-medium hover:bg-primary-container transition-all active:scale-95 shadow-sm">
<span class="material-symbols-outlined text-[20px]" data-icon="add">add</span>
                    Create Event
                </a>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
<div class="bg-surface-container-lowest p-5 rounded-xl border border-outline-variant shadow-sm">
<p class="text-label-md uppercase tracking-wider text-outline mb-1">Total Events</p>
<p class="text-[24px] font-bold text-on-surface">{{ number_format($stats['total']) }}</p>
</div>
<div class="bg-surface-container-lowest p-5 rounded-xl border border-outline-variant shadow-sm">
<p class="text-label-md uppercase tracking-wider text-outline mb-1">Active Events</p>
<p class="text-[24px] font-bold text-primary">{{ number_format($stats['active']) }}</p>
</div>
<div class="bg-surface-container-lowest p-5 rounded-xl border border-outline-variant shadow-sm">
<p class="text-label-md uppercase tracking-wider text-secondary mb-1">Total Registrations</p>
<p class="text-[24px] font-bold text-secondary">{{ number_format($stats['registrations_sum']) }}</p>
</div>
<div class="bg-surface-container-lowest p-5 rounded-xl border border-outline-variant shadow-sm">
<p class="text-label-md uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-1">Total Booking Amount</p>
<p class="text-[24px] font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">{{ \App\Support\AdminDashboardRevenue::formatCents((int) ($stats['total_booking_cents'] ?? 0), $stats['booking_currency'] ?? 'usd') }}</p>
</div>
<div class="bg-surface-container-lowest p-5 rounded-xl border border-outline-variant shadow-sm">
<p class="text-label-md uppercase tracking-wider text-outline mb-1">Drafts</p>
<p class="text-[24px] font-bold text-on-surface">{{ number_format($stats['drafts']) }}</p>
</div>
</div>
<form method="get" action="{{ route('admin.events.index') }}" class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant shadow-sm flex flex-wrap items-center gap-4">
@if($timeFilter !== 'all')
<input type="hidden" name="time" value="{{ $timeFilter }}"/>
@endif
<div class="flex items-center gap-2 px-3 py-1.5 bg-surface rounded-lg border border-outline-variant">
<span class="material-symbols-outlined text-[18px] text-outline">filter_list</span>
<select name="status" class="bg-transparent border-none text-[14px] focus:ring-0 p-0 pr-8 cursor-pointer" onchange="this.form.submit()">
<option value="" @selected($statusFilter === 'all')>All Statuses</option>
<option value="active" @selected($statusFilter === 'active')>Active</option>
<option value="completed" @selected($statusFilter === 'completed')>Completed</option>
<option value="draft" @selected($statusFilter === 'draft')>Draft</option>
</select>
</div>
<div class="flex items-center gap-2 px-3 py-1.5 bg-surface rounded-lg border border-outline-variant">
<span class="material-symbols-outlined text-[18px] text-outline">calendar_month</span>
<select name="time" class="bg-transparent border-none text-[14px] focus:ring-0 p-0 pr-8 cursor-pointer" onchange="this.form.submit()">
<option value="" @selected($timeFilter === 'all')>All Time</option>
<option value="this_week" @selected($timeFilter === 'this_week')>This Week</option>
<option value="this_month" @selected($timeFilter === 'this_month')>This Month</option>
<option value="next_3_months" @selected($timeFilter === 'next_3_months')>Next 3 Months</option>
</select>
</div>
<div class="flex flex-1 min-w-[12rem] items-center gap-2">
<input name="q" value="{{ request('q') }}" type="search" placeholder="Search title, category, city..." class="flex-1 bg-surface border border-outline-variant rounded-lg px-3 py-2 text-[14px]"/>
<button type="submit" class="px-3 py-2 rounded-lg border border-outline-variant text-[14px] font-medium hover:bg-surface-container-high">Search</button>
</div>
<div class="text-[14px] text-on-surface-variant w-full md:w-auto md:text-right">
@if($events->total() > 0)
                    Showing <span class="font-semibold text-on-surface">{{ $events->firstItem() }}-{{ $events->lastItem() }}</span> of <span class="font-semibold text-on-surface">{{ $events->total() }}</span> events
@else
                    No events to display
@endif
                </div>
</form>
<div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-surface-container-low border-b border-outline-variant">
<th class="w-12 px-3 py-4"></th>
<th class="px-6 py-4 text-label-md uppercase tracking-wider text-outline font-semibold">Event Name</th>
<th class="px-6 py-4 text-label-md uppercase tracking-wider text-outline font-semibold">Date</th>
<th class="px-6 py-4 text-label-md uppercase tracking-wider text-outline font-semibold">Location</th>
<th class="px-6 py-4 text-label-md uppercase tracking-wider text-outline font-semibold">Status</th>
<th class="px-6 py-4 text-label-md uppercase tracking-wider text-outline font-semibold">Registrations</th>
<th class="w-20 px-3 py-4 text-center text-label-md uppercase tracking-wider text-outline font-semibold whitespace-nowrap">Actions</th>
</tr>
</thead>
@forelse($events as $ev)
@php
    $catLabel = $ev->categoryLabel();
    $icon = match (true) {
        str_contains(strtolower($catLabel), 'tech') => 'hub',
        str_contains(strtolower($catLabel), 'leadership') || str_contains(strtolower($catLabel), 'executive') => 'diversity_3',
        str_contains(strtolower($catLabel), 'internal') => 'rocket_launch',
        default => 'stadium',
    };
@endphp
<tbody class="event-block border-b border-outline-variant" data-bookings-url="{{ route('admin.events.bookings.data', $ev) }}" data-event-id="{{ $ev->id }}">
<tr class="event-row-main hover:bg-surface-container/50 transition-colors">
<td class="px-3 py-4 align-top">
<button type="button" class="event-bookings-toggle mt-0.5 p-1.5 rounded-lg text-on-surface-variant hover:bg-white hover:text-primary border border-outline-variant/40 hover:border-outline-variant transition-colors" title="Bookings &amp; attendees" aria-expanded="false" aria-label="Show bookings for this event">
<span class="material-symbols-outlined text-[22px] leading-none block">expand_more</span>
</button>
</td>
<td class="px-6 py-4">
<div class="flex items-center gap-3">
<div class="w-10 h-10 rounded-lg bg-primary-fixed flex items-center justify-center text-primary shrink-0">
<span class="material-symbols-outlined">{{ $icon }}</span>
</div>
<div class="min-w-0">
<p class="text-[14px] font-semibold text-on-surface truncate">{{ $ev->title }}</p>
<p class="text-[12px] text-outline">{{ $catLabel !== '' ? $catLabel : '—' }}</p>
</div>
</div>
</td>
<td class="px-6 py-4 align-top">
<div class="max-w-[12rem] text-[14px] text-on-surface-variant whitespace-normal break-words leading-snug">{{ $ev->dateRangeLabel() }}</div>
</td>
<td class="px-6 py-4 align-top">
<div class="max-w-[12rem] text-[14px] text-on-surface-variant whitespace-normal break-words leading-snug">{{ $ev->locationLabel() }}</div>
</td>
<td class="px-6 py-4">
@if($ev->status === 'active')
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[12px] font-medium bg-green-100 text-green-800">Active</span>
@elseif($ev->status === 'draft')
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[12px] font-medium bg-blue-100 text-blue-800">Draft</span>
@else
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[12px] font-medium bg-surface-container-highest text-on-surface-variant">Completed</span>
@endif
</td>
<td class="px-6 py-4">
<div class="flex flex-col">
@if($ev->capacity > 0)
<span class="text-[14px] font-medium text-on-surface">{{ number_format($ev->registrations_count) }} / {{ number_format($ev->capacity) }}</span>
<div class="w-24 h-1 bg-surface-container rounded-full mt-1">
<div class="bg-primary h-full rounded-full" style="width: {{ $ev->registrationsProgressPercent() }}%"></div>
</div>
@else
<span class="text-[14px] font-medium text-on-surface">{{ number_format($ev->registrations_count) }}</span>
<div class="w-24 h-1 bg-surface-container rounded-full mt-1">
<div class="bg-secondary h-full rounded-full w-full"></div>
</div>
@endif
</div>
</td>
<td class="w-20 px-3 py-4 text-center align-middle">
<div class="relative flex justify-center">
<details class="event-row-actions relative inline-block text-left">
<summary class="list-none cursor-pointer inline-flex items-center justify-center w-9 h-9 rounded-lg border border-outline-variant bg-white text-on-surface-variant hover:bg-surface-container-low hover:text-primary hover:border-primary/30 transition-all shadow-sm [&::-webkit-details-marker]:hidden active:scale-95" aria-label="Event actions" title="Actions">
<span class="material-symbols-outlined text-[22px] leading-none">more_vert</span>
</summary>
<div class="absolute right-0 top-full mt-1.5 w-52 py-1.5 rounded-xl pointer-events-auto" style="z-index: 30;">
<div class="rounded-xl border border-outline-variant bg-white shadow-[0_10px_40px_-10px_rgba(0,0,0,0.2)] overflow-hidden">
<a href="{{ route('admin.events.edit', $ev) }}" class="flex items-center gap-3 px-4 py-2.5 text-[13px] font-medium text-on-surface hover:bg-surface-container-low transition-colors">
<span class="material-symbols-outlined text-[20px] text-primary shrink-0">edit_square</span>
<span>Edit event</span>
</a>
<a href="{{ route('admin.events.register-attendee', $ev) }}" class="flex items-center gap-3 px-4 py-2.5 text-[13px] font-medium text-on-surface hover:bg-surface-container-low transition-colors border-t border-outline-variant/80">
<span class="material-symbols-outlined text-[20px] text-secondary shrink-0">person_add</span>
<span>Register attendee</span>
</a>
<form action="{{ route('admin.events.destroy', $ev) }}" method="post" onsubmit="return confirm('Delete this event permanently? This cannot be undone.');" class="border-t border-outline-variant/80">
@csrf
@method('DELETE')
<button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-[13px] font-medium text-error hover:bg-error-container/40 transition-colors text-left">
<span class="material-symbols-outlined text-[20px] shrink-0">delete</span>
<span>Delete event</span>
</button>
</form>
</div>
</div>
</details>
</div>
</td>
</tr>
<tr class="event-row-expand hidden">
<td colspan="7" class="p-0 border-t border-outline-variant/70 bg-surface-container-low/60">
<div class="event-bookings-panel px-4 py-4">
<p class="text-[13px] text-on-surface-variant py-1">Open the row to load bookings and attendees.</p>
</div>
</td>
</tr>
</tbody>
@empty
<tbody>
<tr>
<td colspan="7" class="px-6 py-12 text-center text-on-surface-variant text-[14px]">No events yet. Create your first event.</td>
</tr>
</tbody>
@endforelse
</table>
@if($events->hasPages())
<div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant">
{{ $events->links('vendor.pagination.tailwind') }}
</div>
@endif
</div>
</div>
