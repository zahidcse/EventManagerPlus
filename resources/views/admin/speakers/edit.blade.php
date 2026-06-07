@extends('admin.layouts.app')

@section('title', 'Edit speaker')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search...'])
<div class="pt-16 pb-24">
<div class="max-w-5xl mx-auto p-8">
<div class="mb-8">
<nav class="flex text-label-md text-on-surface-variant mb-2 gap-2">
<a class="hover:text-primary transition-colors" href="{{ route('admin.speakers.index') }}">Speakers</a>
<span class="material-symbols-outlined text-[14px]">chevron_right</span>
<span class="text-primary font-semibold">Edit</span>
</nav>
<h2 class="text-display-lg font-bold text-on-surface tracking-tight">Edit speaker</h2>
</div>
@include('admin.chunks._speaker-form')
</div>
</div>
@endsection
