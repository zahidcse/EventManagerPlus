@extends('admin.layouts.app')

@section('title', 'Register attendee')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search events...'])
<div class="pt-16 pb-28">
<div class="max-w-7xl mx-auto px-8 pt-6 pb-2">
@if(session('success'))
<div class="mb-6 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md flex items-center gap-2">
<span class="material-symbols-outlined text-primary">check_circle</span>
{{ session('success') }}
</div>
@endif
<nav class="mb-6">
<a href="{{ route('admin.events.index') }}" class="inline-flex items-center gap-2 text-label-md font-semibold text-primary hover:text-primary-container transition-colors">
<span class="material-symbols-outlined text-[20px]">arrow_back</span>
Events list
</a>
</nav>
<h1 class="text-2xl sm:text-[28px] font-bold text-on-surface tracking-tight">Register an attendee</h1>
<p class="text-body-md text-on-surface-variant mt-1 max-w-2xl">{{ $event->title }}</p>
</div>
@include('admin.chunks._event-register-attendee')
</div>
@endsection
