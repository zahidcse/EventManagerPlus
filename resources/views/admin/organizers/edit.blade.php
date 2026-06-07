@extends('admin.layouts.app')

@section('title', 'Edit Organizer')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search organizers, events, or reports...'])
<div class="pt-16 pb-24 admin-has-fixed-footer">
<div class="max-w-5xl mx-auto p-8">
<div class="mb-8">
<nav class="flex text-label-md text-on-surface-variant mb-2 gap-2">
<a class="hover:text-primary transition-colors" href="{{ route('admin.organizers.index') }}">Organizers</a>
<span class="material-symbols-outlined text-[14px]">chevron_right</span>
<span class="text-primary font-semibold">Edit Organizer</span>
</nav>
<h2 class="text-display-lg font-bold text-on-surface tracking-tight">Edit Organizer</h2>
<p class="text-body-lg text-on-surface-variant mt-1">Update partner details and account settings.</p>
</div>
@include('admin.chunks._organizer-form')
</div>
</div>
@endsection
