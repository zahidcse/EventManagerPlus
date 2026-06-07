@extends('admin.layouts.app')

@section('title', 'Edit category')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search events...'])
<div class="mt-16 p-8 max-w-xl pb-32 space-y-6">
<div>
<a href="{{ route('admin.event-categories.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline mb-4">
<span class="material-symbols-outlined text-[18px]">arrow_back</span>
Event categories
</a>
<h2 class="text-2xl font-bold text-on-surface">Edit category</h2>
<p class="text-on-surface-variant mt-1">Renaming updates this label everywhere linked events use it.</p>
</div>
<div class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm">
<form method="post" action="{{ route('admin.event-categories.update', $category) }}" class="space-y-5">
@csrf
@method('PUT')
<div class="space-y-1">
<label class="text-label-md font-semibold text-on-surface" for="cat-name">Name</label>
<input id="cat-name" type="text" name="name" value="{{ old('name', $category->name) }}" required maxlength="120" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25 @error('name') border-error @enderror"/>
@error('name')
<p class="text-error text-sm">{{ $message }}</p>
@enderror
</div>
<div class="flex flex-wrap items-center gap-3 pt-2">
<button type="submit" class="px-6 py-2.5 rounded-lg bg-primary text-white text-sm font-bold hover:bg-primary-container">Save changes</button>
<a href="{{ route('admin.event-categories.index') }}" class="px-6 py-2.5 rounded-lg border border-outline-variant text-on-surface text-sm font-semibold hover:bg-surface-container-low">Cancel</a>
</div>
</form>
</div>
</div>
@endsection
