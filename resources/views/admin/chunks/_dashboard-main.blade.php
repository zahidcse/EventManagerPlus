@php
    $stats = $overview['stats'];
    $revenue = $overview['revenue'] ?? ['available' => false, 'total_cents' => 0, 'breakdown' => []];
    $revenueCurrency = $revenue['currency'] ?? 'usd';
    $formatRevenue = fn (int $cents) => \App\Support\AdminDashboardRevenue::formatCents($cents, $revenueCurrency);
@endphp
<!-- TopNavBar Component -->
<header class="sticky top-0 z-40 bg-surface dark:bg-surface-dim border-b border-outline-variant dark:border-outline flex justify-end items-center px-8 w-full h-16">
<div class="flex items-center gap-6">
@include('admin.partials.admin-booking-notifications')
<div class="h-8 w-px bg-outline-variant dark:bg-outline shrink-0"></div>
@include('admin.partials.admin-user-dropdown', ['compactUserLabels' => true, 'avatarSize' => 'w-8 h-8', 'avatarTextClass' => 'text-xs'])
</div>
</header>
<!-- Dashboard Canvas -->
<div class="p-8 pb-12 space-y-8">
<!-- Header Section -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
<div>
<h2 class="font-display-lg text-display-lg text-on-surface">Dashboard Overview</h2>
<p class="font-body-md text-body-md text-on-surface-variant">Live counts from your organizers and events.</p>
</div>
<div class="flex items-center gap-3">
<span class="inline-flex items-center gap-2 px-4 py-2 bg-surface-container-lowest border border-outline-variant rounded-lg text-body-md text-on-surface-variant">
<span class="material-symbols-outlined text-outline text-[20px]" data-icon="calendar_month">calendar_month</span>
<span>As of {{ now()->format('M j, Y') }}</span>
</span>
<a href="{{ $editionPremiumUrl ?? \App\Support\Edition::premiumUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-amber-300 bg-amber-50 text-amber-950 text-body-md font-semibold hover:bg-amber-100 active:scale-95 transition-all">
<span class="material-symbols-outlined text-[20px]">shopping_cart</span>
<span>Purchase Pro</span>
</a>
<a href="{{ route('admin.events.create') }}" class="flex items-center gap-2 px-4 py-2 bg-primary-container text-white rounded-lg text-body-md font-semibold hover:opacity-90 active:scale-95 transition-all">
<span class="material-symbols-outlined" data-icon="add">add</span>
<span>Create Event</span>
</a>
</div>
</div>
<!-- Metrics Row -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 flex flex-col justify-between">
<div class="flex justify-between items-start">
<span class="material-symbols-outlined p-2 bg-secondary-container text-primary rounded-lg" data-icon="event">event</span>
<div class="flex items-center gap-1 text-[12px] font-bold text-primary bg-primary-fixed/20 px-2 py-1 rounded">
<span class="material-symbols-outlined text-[14px]" data-icon="add_circle">add_circle</span>
<span>{{ $overview['events_this_month'] }} this mo.</span>
</div>
</div>
<div class="mt-4">
<p class="text-on-surface-variant text-label-md font-label-md uppercase tracking-wide">Total Events</p>
<h3 class="text-[28px] font-bold text-on-surface mt-1">{{ number_format($stats['total']) }}</h3>
<p class="text-on-surface-variant text-[12px] mt-1">{{ number_format($overview['organizers_count']) }} organizers</p>
</div>
</div>
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 flex flex-col justify-between">
<div class="flex justify-between items-start">
<span class="material-symbols-outlined p-2 bg-secondary-container text-primary rounded-lg" data-icon="assignment_ind">assignment_ind</span>
<span class="text-on-surface-variant text-[12px] font-medium">All events</span>
</div>
<div class="mt-4">
<p class="text-on-surface-variant text-label-md font-label-md uppercase tracking-wide">Total Registrations</p>
<h3 class="text-[28px] font-bold text-on-surface mt-1">{{ number_format($stats['registrations_sum']) }}</h3>
</div>
</div>
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 flex flex-col justify-between">
<div class="flex justify-between items-start">
<span class="material-symbols-outlined p-2 bg-secondary-container text-primary rounded-lg" data-icon="event_available">event_available</span>
@if($stats['active_now'] > 0)
<div class="flex items-center gap-1 text-[12px] font-bold text-primary bg-primary-fixed px-2 py-1 rounded">
<span class="material-symbols-outlined text-[14px]" data-icon="bolt">bolt</span>
<span>{{ number_format($stats['active_now']) }} live</span>
</div>
@endif
</div>
<div class="mt-4">
<p class="text-on-surface-variant text-label-md font-label-md uppercase tracking-wide">Active Events</p>
<h3 class="text-[28px] font-bold text-on-surface mt-1">{{ number_format($stats['active']) }}</h3>
<p class="text-on-surface-variant text-[12px] mt-1">
Published &amp; open
@if($stats['active_now'] > 0)
· {{ number_format($stats['active_now']) }} in progress now
@endif
</p>
</div>
</div>
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 flex flex-col justify-between">
<div class="flex justify-between items-start">
<span class="material-symbols-outlined p-2 bg-secondary-container text-primary rounded-lg" data-icon="event_seat">event_seat</span>
<span class="text-on-surface-variant text-[12px] font-medium">Seats tracked</span>
</div>
<div class="mt-4">
<p class="text-on-surface-variant text-label-md font-label-md uppercase tracking-wide">Capacity Used</p>
<h3 class="text-[28px] font-bold text-on-surface mt-1">@if($overview['total_capacity'] > 0){{ $overview['capacity_used_percent'] }}%@else—@endif</h3>
<p class="text-on-surface-variant text-[12px] mt-1">
@if($overview['total_capacity'] > 0)
{{ number_format($stats['registrations_sum']) }} / {{ number_format($overview['total_capacity']) }} seats
@else
Set ticket quantities to track fill rate
@endif
</p>
</div>
</div>
</div>

@if($revenue['available'] ?? false)
<section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6 mb-8">
<div>
<h4 class="font-headline-md text-headline-md text-on-surface">Revenue</h4>
<p class="text-body-md text-on-surface-variant mt-1">Collected online payments plus estimated offline ticket value (early-bird pricing applied per seat).</p>
</div>
<div class="flex flex-wrap gap-6 lg:gap-10">
<div>
<p class="text-label-md font-label-md uppercase tracking-wide text-on-surface-variant">Total</p>
<p class="text-[32px] font-bold text-on-surface mt-1 tabular-nums">{{ $formatRevenue((int) ($revenue['total_cents'] ?? 0)) }}</p>
<p class="text-[12px] text-on-surface-variant mt-1">{{ $formatRevenue((int) ($revenue['online_cents'] ?? 0)) }} online · {{ $formatRevenue((int) ($revenue['offline_cents'] ?? 0)) }} offline est.</p>
</div>
<div>
<p class="text-label-md font-label-md uppercase tracking-wide text-on-surface-variant">This month</p>
<p class="text-[28px] font-bold text-primary mt-1 tabular-nums">{{ $formatRevenue((int) ($revenue['this_month_cents'] ?? 0)) }}</p>
<p class="text-[12px] text-on-surface-variant mt-1">{{ number_format((int) ($revenue['paid_orders'] ?? 0)) }} paid orders</p>
</div>
</div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
<div class="xl:col-span-7 space-y-6">
<h5 class="text-label-md font-semibold text-on-surface-variant uppercase tracking-wide">Breakdown by channel</h5>
@if(count($revenue['breakdown'] ?? []) > 0)
<div class="space-y-4">
@foreach($revenue['breakdown'] as $row)
@php
  $pct = ($revenue['total_cents'] ?? 0) > 0 ? (int) round(($row['cents'] / $revenue['total_cents']) * 100) : 0;
@endphp
<div>
<div class="flex items-center justify-between gap-3 mb-1.5">
<span class="text-body-md font-semibold text-on-surface">{{ $row['label'] }}</span>
<span class="text-body-sm text-on-surface-variant tabular-nums">
{{ $formatRevenue((int) $row['cents']) }}
@if(($row['count'] ?? 0) > 0)
@php
  $isOnline = in_array($row['key'] ?? '', ['stripe', 'paypal', 'razorpay', 'sslcommerz', 'other_online'], true);
  $countLabel = $isOnline
    ? ((int) $row['count'] === 1 ? 'order' : 'orders')
    : ((int) $row['count'] === 1 ? 'seat' : 'seats');
@endphp
<span class="text-outline">· {{ number_format((int) $row['count']) }} {{ $countLabel }}</span>
@endif
</span>
</div>
<div class="h-2.5 rounded-full bg-surface-container-low overflow-hidden">
<div class="{{ $row['color'] }} h-full rounded-full transition-all" style="width: {{ max($pct, $row['cents'] > 0 ? 4 : 0) }}%;"></div>
</div>
</div>
@endforeach
</div>
@else
<p class="text-body-md text-on-surface-variant">No paid orders yet. Revenue appears here after Stripe, PayPal, Razorpay, SSLCommerz, or offline bookings.</p>
@endif
</div>

<div class="xl:col-span-5 space-y-6">
<h5 class="text-label-md font-semibold text-on-surface-variant uppercase tracking-wide">Last 7 days (online)</h5>
<div class="relative h-[200px] w-full bg-surface-container-low rounded-lg overflow-hidden flex items-end px-4 pb-4 gap-2">
@foreach($revenue['chart_days'] ?? [] as $day)
@php
  $h = ($day['cents'] ?? 0) === 0 ? 4 : max(12, $day['height_percent'] ?? 0);
@endphp
<div class="flex-1 rounded-t-sm relative group bg-emerald-500/70 hover:bg-emerald-500 transition-colors" style="height: {{ $h }}%; min-height: 4px;" title="{{ $day['date'] }}: {{ $formatRevenue((int) ($day['cents'] ?? 0)) }}">
<div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-inverse-surface text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">{{ $formatRevenue((int) ($day['cents'] ?? 0)) }}</div>
</div>
@endforeach
</div>
<div class="flex justify-between mt-2 px-2 text-[11px] text-outline font-medium uppercase tracking-wider">
@foreach($revenue['chart_days'] ?? [] as $day)
<span>{{ $day['label'] }}</span>
@endforeach
</div>

@if(count($revenue['top_events'] ?? []) > 0)
<div class="pt-4 border-t border-outline-variant/60">
<h5 class="text-label-md font-semibold text-on-surface-variant uppercase tracking-wide mb-3">Top events by online revenue</h5>
<ul class="space-y-2">
@foreach($revenue['top_events'] as $evRow)
<li class="flex items-center justify-between gap-3 text-body-sm">
<a href="{{ route('admin.events.edit', $evRow['event_id']) }}" class="font-semibold text-on-surface hover:text-primary truncate">{{ $evRow['title'] }}</a>
<span class="shrink-0 tabular-nums text-on-surface-variant">{{ $formatRevenue((int) $evRow['cents']) }} <span class="text-outline">({{ (int) $evRow['orders'] }})</span></span>
</li>
@endforeach
</ul>
</div>
@endif
</div>
</div>
</section>
@endif

<!-- Main Grid Content -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
<!-- Left: New events chart -->
<div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
<div class="flex justify-between items-center mb-8">
<div>
<h4 class="font-headline-md text-headline-md">New events (last 7 days)</h4>
<p class="text-on-surface-variant text-body-md">Count of events created on each day — registration history is not stored per day yet.</p>
</div>
</div>
<div class="relative h-[320px] w-full bg-surface-container-low rounded-lg overflow-hidden flex items-end px-4 pb-4 gap-2 sm:gap-4">
@foreach($overview['chart_days'] as $day)
@php
    $h = $day['count'] === 0 ? 6 : max(12, $day['height_percent']);
    $isPeak = $day['count'] === max(array_column($overview['chart_days'], 'count')) && $day['count'] > 0;
@endphp
<div class="flex-1 rounded-t-sm relative group transition-colors {{ $isPeak ? 'bg-primary-container hover:opacity-90' : 'bg-primary/20 hover:bg-primary/40' }}" style="height: {{ $h }}%; min-height: 4px;" title="{{ $day['date'] }}: {{ $day['count'] }} created">
<div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-inverse-surface text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">{{ $day['count'] }}</div>
</div>
@endforeach
</div>
<div class="flex justify-between mt-4 px-2 sm:px-4 text-[12px] text-outline font-medium uppercase tracking-wider">
@foreach($overview['chart_days'] as $day)
<span>{{ $day['label'] }}</span>
@endforeach
</div>
</div>
<!-- Right: Upcoming Events List -->
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 flex flex-col">
<div class="flex justify-between items-center mb-6">
<h4 class="font-headline-md text-headline-md">Upcoming Events</h4>
<a href="{{ route('admin.events.index') }}" class="text-primary font-semibold text-body-md hover:underline">View all</a>
</div>
<div class="space-y-4 flex-1">
@forelse($overview['upcoming_events'] as $ev)
@php
    $pct = $ev->registrationsProgressPercent();
@endphp
<a href="{{ route('admin.events.edit', $ev) }}" class="flex items-center gap-4 p-3 border border-outline-variant/30 rounded-lg hover:border-primary/30 hover:bg-surface-container-low transition-all group text-left w-full">
@if($ev->starts_at)
<div class="w-12 h-12 bg-secondary-fixed rounded flex flex-col items-center justify-center text-primary leading-tight shrink-0">
<span class="text-[10px] font-bold uppercase">{{ $ev->starts_at->format('M') }}</span>
<span class="text-lg font-extrabold">{{ $ev->starts_at->format('j') }}</span>
</div>
@else
<div class="w-12 h-12 bg-surface-container rounded flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-outline">event</span>
</div>
@endif
<div class="flex-1 overflow-hidden min-w-0">
<h5 class="font-bold text-on-surface truncate group-hover:text-primary transition-colors">{{ $ev->title }}</h5>
<div class="flex items-center gap-2 mt-1">
<span class="w-2 h-2 rounded-full shrink-0 {{ $ev->capacity <= 0 ? 'bg-outline-variant' : ($pct >= 80 ? 'bg-green-500' : ($pct >= 40 ? 'bg-yellow-500' : 'bg-outline-variant')) }}"></span>
<span class="text-xs text-on-surface-variant truncate">
@if($ev->capacity > 0)
{{ $pct }}% · {{ $ev->status === 'draft' ? 'Draft' : ucfirst((string) $ev->status) }}
@else
{{ $ev->status === 'draft' ? 'Draft' : ucfirst((string) $ev->status) }}
@endif
</span>
</div>
</div>
<span class="material-symbols-outlined text-outline-variant shrink-0" data-icon="chevron_right">chevron_right</span>
</a>
@empty
<p class="text-body-md text-on-surface-variant py-4">No upcoming events. Create one or set future start dates.</p>
@endforelse
</div>
<div class="mt-6 pt-6 border-t border-outline-variant">
<div class="bg-primary-container/10 p-4 rounded-xl flex items-center gap-4">
<div class="w-10 h-10 rounded-full bg-primary-container flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-white" data-icon="bolt">bolt</span>
</div>
<div>
<p class="text-[13px] font-bold text-primary">Tip</p>
<p class="text-[12px] text-on-primary-fixed-variant leading-tight">
@if($stats['drafts'] > 0)
You have {{ number_format($stats['drafts']) }} draft {{ Str::plural('event', $stats['drafts']) }} — open the wizard to finish ticketing and publish.
@else
You are up to date on drafts. Use “Create Event” to grow your calendar.
@endif
</p>
</div>
</div>
</div>
</div>
</div>
<!-- Bottom Section -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
<div class="lg:col-span-1 bg-surface-container-lowest border border-outline-variant rounded-xl p-6">
<h4 class="font-bold text-on-surface mb-4">Recent Activity</h4>
<div class="space-y-6 relative before:absolute before:left-3 before:top-2 before:bottom-2 before:w-px before:bg-outline-variant">
@forelse($overview['recent_activity'] as $item)
<div class="relative pl-8">
@if($item['kind'] === 'created')
<span class="absolute left-0 top-0 w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
<span class="material-symbols-outlined text-[14px] text-green-700" data-icon="event_available">event_available</span>
</span>
@else
<span class="absolute left-0 top-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center">
<span class="material-symbols-outlined text-[14px] text-blue-700" data-icon="edit">edit</span>
</span>
@endif
<p class="text-[13px] text-on-surface leading-snug"><span class="font-bold truncate block">{{ \Illuminate\Support\Str::limit($item['title'], 48) }}</span>
<span class="font-normal text-on-surface-variant">{{ $item['kind'] === 'created' ? 'Created' : 'Updated' }}</span></p>
<span class="text-[11px] text-outline">{{ $item['at']->diffForHumans() }}</span>
</div>
@empty
<p class="text-body-sm text-on-surface-variant">No events yet.</p>
@endforelse
</div>
</div>
<div class="lg:col-span-3 bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden">
<div class="p-6 border-b border-outline-variant flex justify-between items-center">
<h4 class="font-bold text-on-surface">Recent Events</h4>
<a href="{{ route('admin.events.index') }}" class="flex items-center gap-1 text-[13px] text-primary font-semibold hover:underline">
<span class="material-symbols-outlined text-[16px]" data-icon="arrow_forward">arrow_forward</span>
<span>View all</span>
</a>
</div>
<table class="w-full text-left">
<thead>
<tr class="bg-surface-container-low border-b border-outline-variant">
<th class="px-6 py-3 font-label-md text-label-md text-outline uppercase tracking-wider">Event</th>
<th class="px-6 py-3 font-label-md text-label-md text-outline uppercase tracking-wider">Organizer</th>
<th class="px-6 py-3 font-label-md text-label-md text-outline uppercase tracking-wider">Date</th>
<th class="px-6 py-3 font-label-md text-label-md text-outline uppercase tracking-wider">Registrations</th>
<th class="px-6 py-3 font-label-md text-label-md text-outline uppercase tracking-wider">Status</th>
<th class="px-6 py-3 font-label-md text-label-md text-outline uppercase tracking-wider text-right">Action</th>
</tr>
</thead>
<tbody class="divide-y divide-surface-container">
@forelse($overview['recent_events'] as $ev)
<tr class="hover:bg-surface-container-low transition-colors group">
<td class="px-6 py-4 text-body-md font-medium text-on-surface max-w-[200px] truncate">{{ $ev->title }}</td>
<td class="px-6 py-4 text-body-md text-on-surface-variant">{{ $ev->organizer?->name ?? '—' }}</td>
<td class="px-6 py-4 text-body-md text-on-surface-variant whitespace-nowrap">{{ $ev->dateRangeLabel() }}</td>
<td class="px-6 py-4 text-body-md text-on-surface-variant">
@if($ev->capacity > 0)
{{ number_format($ev->registrations_count) }} / {{ number_format($ev->capacity) }}
@else
{{ number_format($ev->registrations_count) }}
@endif
</td>
<td class="px-6 py-4">
@if($ev->status === 'active')
<span class="px-2 py-1 rounded text-[11px] font-bold bg-green-100 text-green-700">Active</span>
@elseif($ev->status === 'draft')
<span class="px-2 py-1 rounded text-[11px] font-bold bg-blue-100 text-blue-700">Draft</span>
@else
<span class="px-2 py-1 rounded text-[11px] font-bold bg-surface-container-highest text-on-surface-variant">Completed</span>
@endif
</td>
<td class="px-6 py-4 text-right">
<a href="{{ route('admin.events.edit', $ev) }}" class="text-primary text-[13px] font-semibold hover:underline">Edit</a>
</td>
</tr>
@empty
<tr>
<td colspan="6" class="px-6 py-12 text-center text-on-surface-variant text-body-md">No events yet.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
</div>
</div>






