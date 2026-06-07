@extends('admin.layouts.app')

@section('title', 'Check-in')

@section('content')

@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search…'])

<main class="mt-16 p-8 min-h-screen max-w-2xl">
  @if(session('success'))
    <div class="mb-4 rounded-lg border border-primary/40 bg-primary/10 px-4 py-3 text-sm text-on-surface">{{ session('success') }}</div>
  @endif
  @if(session('info'))
    <div class="mb-4 rounded-lg border border-outline-variant bg-surface-container-low px-4 py-3 text-sm text-on-surface">{{ session('info') }}</div>
  @endif

  <h1 class="text-2xl font-semibold text-on-surface mb-2">Attendee check-in</h1>
  <p class="text-sm text-on-surface-variant mb-6">Confirm details before checking in.</p>

  @php
    $ev = $booking->event;
  @endphp

  <div class="rounded-xl border border-outline-variant bg-white dark:bg-surface-container-low p-6 space-y-4">
    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Event</p>
      <p class="text-lg font-medium text-on-surface">{{ $ev ? $ev->title : '—' }}</p>
      @if($ev?->starts_at)
        <p class="text-sm text-on-surface-variant mt-1">{{ $ev->starts_at->format('M j, Y g:i A') }}</p>
      @endif
      @if($booking->occurrence_date)
        <p class="text-sm font-medium text-on-surface mt-2">Session day: {{ $booking->occurrence_date->format('l, M j, Y') }}</p>
      @endif
    </div>

    <div class="border-t border-outline-variant pt-4">
      <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Attendee</p>
      <p class="text-on-surface font-medium">{{ $booking->attendee_name }}</p>
      @if($booking->email)
        <p class="text-sm text-on-surface-variant">{{ $booking->email }}</p>
      @endif
      @if($booking->phone)
        <p class="text-sm text-on-surface-variant">{{ $booking->phone }}</p>
      @endif
    </div>

    <div class="border-t border-outline-variant pt-4">
      <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Ticket</p>
      <p class="text-on-surface">{{ $booking->ticket?->name ?? '—' }}</p>
      <p class="text-sm text-on-surface-variant mt-1">Booking #{{ $booking->id }} · {{ $booking->created_at?->format('M j, Y') }}</p>
    </div>

    <div class="border-t border-outline-variant pt-4">
      <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant">Status</p>
      @if($booking->checked_in_at)
        <p class="text-on-surface font-semibold text-primary">Checked in</p>
        <p class="text-sm text-on-surface-variant">{{ $booking->checked_in_at->format('M j, Y g:i A') }}</p>
      @else
        <p class="text-on-surface font-semibold">Not checked in</p>
        <form method="post" action="{{ route('admin.check-in.store', ['token' => $booking->check_in_token]) }}" class="mt-4">
          @csrf
          <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-primary text-white text-sm font-semibold hover:opacity-95">
            Confirm check-in
          </button>
        </form>
      @endif
    </div>
  </div>

  <p class="text-xs text-on-surface-variant mt-6">
    <a href="{{ $ev ? route('admin.events.edit', $ev) : route('admin.events.index') }}" class="text-primary font-medium hover:underline">Back to event</a>
  </p>
</main>

@endsection
