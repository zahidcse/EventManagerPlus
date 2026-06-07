@extends('admin.layouts.app')

@section('title', 'Reports')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search…'])
<main class="mt-16 p-8 min-h-screen">
    <div class="max-w-7xl mx-auto space-y-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h2 class="text-display-lg font-bold text-on-surface">Registration reports</h2>
                <p class="text-body-lg text-on-surface-variant max-w-3xl">Filter registrations by schedule, venue,
                    organizer,
                    booking status, and check-in. For natural-language analytics, open <a
                        href="{{ route('admin.report-ai.index') }}"
                        class="text-primary font-medium underline underline-offset-2">AI reporting</a>.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.reports.export', array_filter(request()->query())) }}"
                    class="inline-flex items-center justify-center gap-2 border border-outline-variant text-on-surface px-5 py-2.5 rounded-xl font-semibold shadow-sm hover:bg-surface-container-low transition-all active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[22px]">download</span>
                    Download CSV
                </a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center border-b border-outline-variant gap-8">
            <a href="{{ route('admin.reports.index', request()->query()) }}"
                class="pb-4 text-sm font-bold tracking-tight transition-all border-b-2 {{ $activeTab === 'registrations' ? 'border-primary text-primary' : 'border-transparent text-outline hover:text-on-surface' }}">
                Individual Registrations
            </a>
            <a href="{{ route('admin.reports.orders', request()->query()) }}"
                class="pb-4 text-sm font-bold tracking-tight transition-all border-b-2 {{ $activeTab === 'orders' ? 'border-primary text-primary' : 'border-transparent text-outline hover:text-on-surface' }}">
                Group Orders
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-5 rounded-xl border border-outline-variant flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center text-on-primary-fixed">
                    <span class="material-symbols-outlined">confirmation_number</span>
                </div>
                <div>
                    <p class="text-label-md text-on-surface-variant font-medium">MATCHING REGISTRATIONS</p>
                    <p class="text-headline-lg font-bold">{{ number_format($totalMatching) }}</p>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-outline-variant flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-full bg-secondary-fixed flex items-center justify-center text-on-secondary-fixed">
                    <span class="material-symbols-outlined">payments</span>
                </div>
                <div>
                    <p class="text-label-md text-on-surface-variant font-medium">REVENUE</p>
                    <p class="text-headline-lg font-bold">{{ $revenue['formatted'] ?? '$0.00' }}</p>
                    <p class="text-[11px] text-on-surface-variant mt-1">Paid online orders plus estimated offline seats
                        (early-bird pricing).</p>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-outline-variant">
                <p class="text-label-md text-on-surface-variant font-medium mb-2">Date range applies to events</p>
                <p class="text-body-sm text-on-surface-variant">Shows bookings for events whose schedule overlaps your
                    selected dates. Leave dates empty for all periods.</p>
            </div>
        </div>

        <form method="get" action="{{ route('admin.reports.index') }}"
            class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-1 flex flex-wrap gap-3 items-end">
                    <div class="min-w-[140px] flex-1">
                        <label class="block text-label-md font-medium text-on-surface-variant mb-1.5">From</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                            class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright" />
                    </div>
                    <div class="min-w-[140px] flex-1">
                        <label class="block text-label-md font-medium text-on-surface-variant mb-1.5">To</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                            class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright" />
                    </div>
                </div>
                <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-label-md font-medium text-on-surface-variant mb-1.5">Event</label>
                        <select name="event_id"
                            class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright">
                            <option value="">All events</option>
                            @foreach($eventsForFilter as $ev)
                                <option value="{{ $ev->id }}" @selected(($filters['event_id'] ?? null) === $ev->id)>
                                    {{ $ev->title }}@if($ev->starts_at) — {{ $ev->starts_at->format('M j, Y') }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-label-md font-medium text-on-surface-variant mb-1.5">Organizer</label>
                        <select name="organizer_id"
                            class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright">
                            <option value="">All organizers</option>
                            @foreach($organizersForFilter as $org)
                                <option value="{{ $org->id }}" @selected(($filters['organizer_id'] ?? null) === $org->id)>
                                    {{ $org->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-label-md font-medium text-on-surface-variant mb-1.5">Check-in</label>
                        <select name="check_in"
                            class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright">
                            <option value="" @selected(empty($filters['check_in']))>Any check-in status</option>
                            <option value="checked_in" @selected(($filters['check_in'] ?? null) === 'checked_in')>Checked
                                in</option>
                            <option value="not_checked_in" @selected(($filters['check_in'] ?? null) === 'not_checked_in')>
                                Not checked in</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-label-md font-medium text-on-surface-variant mb-1.5">Payment
                            Status</label>
                        <select name="status"
                            class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright">
                            <option value="">Any status</option>
                            <option value="pending" @selected(($filters['status'] ?? null) === 'pending')>Pending</option>
                            <option value="pending_offline_payment" @selected(($filters['status'] ?? null) === 'pending_offline_payment')>Pending Offline Payment</option>
                            <option value="confirmed" @selected(($filters['status'] ?? null) === 'confirmed')>Confirmed
                            </option>
                            <option value="cancelled" @selected(($filters['status'] ?? null) === 'cancelled')>Cancelled
                            </option>
                            <option value="checked_in" @selected(($filters['status'] ?? null) === 'checked_in')>Checked In
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-label-md font-medium text-on-surface-variant mb-1.5">Location (venue
                            text)</label>
                        <input type="search" name="location" value="{{ $filters['location'] }}"
                            placeholder="City, street, country…"
                            class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright" />
                    </div>
                </div>
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-container text-white rounded-lg font-semibold text-body-md hover:opacity-90">
                    <span class="material-symbols-outlined text-[20px]">filter_alt</span>
                    Apply filters
                </button>
                <a href="{{ route('admin.reports.index') }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 border border-outline-variant rounded-lg text-body-md font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                    Reset
                </a>
            </div>
        </form>

        <div class="registration-reports-grid grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @forelse($bookings as $b)
            @php($ev = $b->event)
            <article
                class="report-card rounded-2xl border border-outline-variant bg-white p-5 shadow-sm hover:shadow-md transition-all group">
                <div class="flex items-start justify-between gap-3 border-b border-outline-variant/50 pb-4 mb-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="material-symbols-outlined text-[18px] text-primary">person</span>
                            <p class="font-bold text-on-surface text-base leading-tight truncate">
                                {{ $b->attendee_name }}
                            </p>
                        </div>
                        <p class="text-[12px] text-on-surface-variant truncate ml-6 opacity-80">
                            {{ $b->email ?? 'No email provided' }}
                        </p>
                    </div>
                    <span
                        class="inline-flex shrink-0 px-2 py-1 rounded-md text-[10px] font-bold bg-surface-container-high text-on-surface uppercase tracking-wider border border-outline-variant/30">
                        {{ $b->status }}
                    </span>
                </div>

                <div class="space-y-4">
                    <div class="bg-surface-container-lowest/50 rounded-xl p-3 border border-outline-variant/30">
                        <dt
                            class="text-[10px] font-bold uppercase tracking-widest text-outline mb-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">event</span> Event
                        </dt>
                        <dd class="text-[13px] font-semibold text-on-surface line-clamp-1">
                            {{ $ev?->title ?? 'Deleted Event' }}
                        </dd>
                        @if($ev && $ev->categoryLabel())
                            <dd class="text-[11px] text-primary font-medium mt-0.5">{{ $ev->categoryLabel() }}</dd>
                        @endif
                    </div>

                    <dl class="grid grid-cols-2 gap-x-4 gap-y-4 text-[12px]">
                        <div class="min-w-0">
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-outline mb-1">Schedule</dt>
                            <dd class="text-on-surface-variant">
                                @if($ev && $ev->starts_at)
                                    <span class="font-medium text-on-surface">{{ $ev->starts_at->format('M j, Y') }}</span>
                                    @if($ev->ends_at && !$ev->starts_at->isSameDay($ev->ends_at))
                                        <span class="block text-[10px] opacity-70">to
                                            {{ $ev->ends_at->format('M j, Y') }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="min-w-0 text-right">
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-outline mb-1">Ticket</dt>
                            <dd class="text-on-surface-variant">
                                <span class="font-medium text-on-surface">{{ $b->ticket?->name ?? 'Standard' }}</span>
                                @if($b->ticket?->price !== null)
                                    <span
                                        class="block text-[11px] text-primary-fixed-dim font-bold mt-0.5">${{ number_format((float) $b->ticket->price, 2) }}</span>
                                @endif
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-outline mb-1">Organizer</dt>
                            <dd class="text-on-surface-variant truncate">{{ $ev?->organizer?->name ?? '—' }}</dd>
                        </div>
                        <div class="min-w-0 text-right">
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-outline mb-1">Booked</dt>
                            <dd class="text-on-surface-variant">{{ $b->created_at?->format('M j, Y H:i') }}</dd>
                        </div>

                        <div class="col-span-2 min-w-0 pt-2 border-t border-outline-variant/30">
                            <dt
                                class="text-[10px] font-bold uppercase tracking-widest text-outline mb-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">payments</span> Payment Reference
                            </dt>
                            <dd class="text-[12px] text-on-surface-variant">
                                @if($b->offline_payment_reference)
                                    <span class="font-medium text-on-surface">
                                        @if($b->offline_payment_method === 'cash') Cash
                                        @elseif($b->offline_payment_method === 'bank_transfer') Bank @else Payment @endif
                                    </span>
                                    <span
                                        class="ml-1 px-1.5 py-0.5 bg-surface-container rounded text-[11px] font-mono">{{ $b->offline_payment_reference }}</span>
                                @else
                                    <span class="italic text-[11px] opacity-60">No offline reference</span>
                                @endif
                            </dd>
                        </div>

                        @if($ev)
                            <div class="col-span-2 min-w-0 pt-2 border-t border-outline-variant/30">
                                <dt
                                    class="text-[10px] font-bold uppercase tracking-widest text-outline mb-1 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">pin_drop</span> Location
                                </dt>
                                <dd class="text-[11px] text-on-surface-variant line-clamp-1">
                                    {{ $ev->fullVenueAddressLine() ?: $ev->locationLabel() }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </article>
            @empty
            <div
                class="col-span-full py-16 bg-surface-container-lowest rounded-2xl border border-dashed border-outline-variant flex flex-col items-center justify-center text-center">
                <span class="material-symbols-outlined text-[48px] text-outline mb-3 opacity-30">search_off</span>
                <p class="text-on-surface-variant font-medium">No registrations match these filters.</p>
                <p class="text-sm text-outline mt-1">Try adjusting your date range or event selection.</p>
            </div>
            @endforelse
        </div>
        @if($bookings->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-container-lowest">
                {{ $bookings->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>
    </div>
</main>
@endsection