@extends('admin.layouts.app')

@section('title', 'Group Orders Report')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search…'])
<main class="mt-16 p-8 min-h-screen">
    <div class="max-w-7xl mx-auto space-y-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h2 class="text-display-lg font-bold text-on-surface">Registration reports</h2>
                <p class="text-body-lg text-on-surface-variant max-w-3xl">Filter registrations by schedule, venue, organizer,
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
                <div class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center text-on-primary-fixed">
                    <span class="material-symbols-outlined">shopping_cart</span>
                </div>
                <div>
                    <p class="text-label-md text-on-surface-variant font-medium">TOTAL GROUP ORDERS</p>
                    <p class="text-headline-lg font-bold">{{ number_format($totalMatching) }}</p>
                </div>
            </div>
            <div class="md:col-span-2 bg-surface-container-lowest p-5 rounded-xl border border-outline-variant flex flex-col justify-center">
                <p class="text-label-md text-on-surface-variant font-medium mb-1">Grouping Logic</p>
                <p class="text-body-sm text-on-surface-variant">Combined orders are grouped by their unique Checkout ID. This view summarizes multiple tickets purchased in a single transaction.</p>
            </div>
        </div>

        <form method="get" action="{{ route('admin.reports.orders') }}" class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm">
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
                                    {{ $ev->title }}@if($ev->starts_at)
                                        — {{ $ev->starts_at->format('M j, Y') }}
                                    @endif
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
                                    {{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-label-md font-medium text-on-surface-variant mb-1.5">Check-in</label>
                        <select name="check_in"
                            class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright">
                            <option value="" @selected(empty($filters['check_in']))>Any check-in status</option>
                            <option value="checked_in" @selected(($filters['check_in'] ?? null) === 'checked_in')>Checked in
                            </option>
                            <option value="not_checked_in" @selected(($filters['check_in'] ?? null) === 'not_checked_in')>Not
                                checked in</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-label-md font-medium text-on-surface-variant mb-1.5">Payment Status</label>
                        <select name="status"
                            class="w-full px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright">
                            <option value="">Any status</option>
                            <option value="pending" @selected(($filters['status'] ?? null) === 'pending')>Pending</option>
                            <option value="pending_offline_payment" @selected(($filters['status'] ?? null) === 'pending_offline_payment')>Pending Offline Payment</option>
                            <option value="confirmed" @selected(($filters['status'] ?? null) === 'confirmed')>Confirmed</option>
                            <option value="cancelled" @selected(($filters['status'] ?? null) === 'cancelled')>Cancelled</option>
                            <option value="checked_in" @selected(($filters['status'] ?? null) === 'checked_in')>Checked In</option>
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
                <a href="{{ route('admin.reports.orders') }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 border border-outline-variant rounded-lg text-body-md font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                    Reset
                </a>
            </div>
        </form>

        <div class="group-orders-grid grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @forelse($orders as $order)
                @php($ev = $order->event)
                <article class="order-card rounded-2xl border border-outline-variant bg-white p-6 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                    <!-- Quantity Badge -->
                    <div class="absolute top-0 right-0 p-3">
                         <div class="bg-primary text-white text-[12px] font-bold px-3 py-1 rounded-bl-xl shadow-sm">
                            {{ $order->ticket_count }} Tickets
                         </div>
                    </div>

                    <div class="border-b border-outline-variant/50 pb-4 mb-4">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center">
                                <span class="material-symbols-outlined text-on-surface-variant">account_balance_wallet</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-on-surface text-base leading-tight truncate">{{ $order->main_attendee_name }}</p>
                                <p class="text-[11px] text-outline truncate">{{ $order->order_group_id }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <!-- Event Context -->
                        <div class="bg-surface-container-lowest/50 rounded-xl p-3 border border-outline-variant/30">
                            <dt class="text-[10px] font-bold uppercase tracking-widest text-outline mb-1">Target Event</dt>
                            <dd class="text-[13px] font-bold text-on-surface truncate">{{ $ev?->title ?? 'Deleted Event' }}</dd>
                            <dd class="text-[11px] text-on-surface-variant mt-1 italic">{{ $order->main_email }}</dd>
                        </div>

                        <!-- Ticket Types & Services -->
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-widest text-outline mb-1.5 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">confirmation_number</span> Ticket Tiers
                                </dt>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($order->ticket_types as $type)
                                        <span class="px-2 py-0.5 bg-secondary-container/10 text-on-secondary-container border border-secondary/20 rounded text-[11px] font-medium">{{ $type }}</span>
                                    @endforeach
                                </div>
                            </div>

                            @if(count($order->parsed_services) > 0)
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-widest text-outline mb-1.5 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">add_task</span> Additional Services
                                </dt>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($order->parsed_services as $svc)
                                        <span class="px-2 py-0.5 bg-primary-container/10 text-on-primary-container border border-primary/20 rounded text-[11px] font-bold">{{ $svc }}</span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Meta Info Row -->
                        <div class="flex items-center justify-between pt-4 border-t border-outline-variant/30 mt-auto">
                            <div class="text-[11px]">
                                <p class="text-outline uppercase font-bold tracking-tighter">Order Date</p>
                                <p class="text-on-surface-variant font-medium">{{ $order->created_at?->format('M j, Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-[10px] font-bold bg-surface-container-high text-on-surface uppercase tracking-wider border border-outline-variant/30">
                                    {{ $order->group_status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-16 bg-surface-container-lowest rounded-2xl border border-dashed border-outline-variant flex flex-col items-center justify-center text-center">
                    <span class="material-symbols-outlined text-[48px] text-outline mb-3 opacity-30">inventory_2</span>
                    <p class="text-on-surface-variant font-medium">No group orders found match these filters.</p>
                </div>
            @endforelse
        </div>

        @if($orders->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-container-lowest mt-8 rounded-xl shadow-sm">
                {{ $orders->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>
</main>
@endsection
