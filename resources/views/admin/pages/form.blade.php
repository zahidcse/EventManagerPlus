@extends('admin.layouts.app')

@php
    $isEdit = isset($page) && $page instanceof \App\Models\Page;
@endphp

@section('title', $isEdit ? 'Edit page' : 'New page')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search...'])
<main class="mt-16 p-8 min-h-screen pb-24">
<div class="max-w-4xl mx-auto">
<nav class="flex text-label-md text-on-surface-variant mb-4 gap-2">
<a class="hover:text-primary transition-colors" href="{{ route('admin.pages.index') }}">Pages</a>
<span>/</span>
<span class="text-on-surface font-medium">{{ $isEdit ? 'Edit' : 'Create' }}</span>
</nav>
@if(session('success'))
<div class="mb-6 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md">{{ session('success') }}</div>
@endif
<form method="post" action="{{ $isEdit ? route('admin.pages.update', $page) : route('admin.pages.store') }}" enctype="multipart/form-data" class="space-y-8">
@csrf
@if($isEdit)
@method('PUT')
@endif
<div class="rounded-2xl border border-outline-variant bg-white shadow-sm p-6 space-y-5">
<h2 class="text-lg font-bold text-on-surface border-b border-outline-variant pb-3">Content</h2>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Title <span class="text-error">*</span></label>
<input type="text" name="title" value="{{ old('title', $page?->title) }}" required class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"/>
@error('title')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">URL slug</label>
<input type="text" name="slug" value="{{ old('slug', $page?->slug) }}" placeholder="auto from title if empty" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono text-sm"/>
<p class="text-body-xs text-on-surface-variant mt-1">Lowercase letters, numbers, and hyphens only.</p>
@error('slug')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Hero image</label>
@if($isEdit && $page->hero_image_path)
<div class="mb-3 rounded-xl overflow-hidden border border-outline-variant max-w-md aspect-video bg-surface-container">
<img src="{{ asset('uploads/'.$page->hero_image_path) }}" alt="" class="w-full h-full object-cover"/>
</div>
@endif
<input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" class="block w-full text-body-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-semibold"/>
@error('hero_image')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Body</label>
@include('admin.partials.rich-text-editor')
<textarea id="page-body-editor" name="body" rows="12" data-admin-tinymce class="w-full min-h-[280px] rounded-xl border border-outline-variant px-3 py-2 text-body-md">{!! old('body', $page?->body ?? '') !!}</textarea>
@error('body')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
</div>
<div class="rounded-2xl border border-outline-variant bg-white shadow-sm p-6 space-y-5">
<h2 class="text-lg font-bold text-on-surface border-b border-outline-variant pb-3">SEO</h2>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Meta title</label>
<input type="text" name="meta_title" value="{{ old('meta_title', $page?->meta_title) }}" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"/>
@error('meta_title')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Meta description</label>
<textarea name="meta_description" rows="3" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none">{{ old('meta_description', $page?->meta_description) }}</textarea>
@error('meta_description')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
</div>
<div class="rounded-2xl border border-outline-variant bg-white shadow-sm p-6 flex flex-wrap gap-6 items-end">
<div class="flex-1 min-w-[200px]">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Status</label>
<select name="status" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
<option value="draft" @selected(old('status', $page?->status ?? 'draft') === 'draft')>Draft</option>
<option value="published" @selected(old('status', $page?->status) === 'published')>Published</option>
</select>
@error('status')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div class="flex-1 min-w-[200px]">
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Publish date</label>
<input type="datetime-local" name="published_at" value="{{ old('published_at', $page?->published_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"/>
@error('published_at')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
</div>
<div class="flex flex-wrap gap-3 justify-end">
<a href="{{ route('admin.pages.index') }}" class="px-5 py-2.5 rounded-xl border border-outline-variant text-on-surface font-semibold hover:bg-surface-container-low">Cancel</a>
<button type="submit" class="px-6 py-2.5 rounded-xl bg-primary text-white font-bold hover:bg-primary-container shadow-md shadow-primary/20">{{ $isEdit ? 'Save changes' : 'Create page' }}</button>
</div>
</form>
</div>
</main>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ta = document.getElementById('page-body-editor');
    if (ta && window.adminRichTextInit) window.adminRichTextInit(ta);
});
</script>
@endpush
@endsection
