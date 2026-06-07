@extends('admin.layouts.app')

@php
    $isEdit = isset($post) && $post instanceof \App\Models\BlogPost;
@endphp

@section('title', $isEdit ? 'Edit blog post' : 'New blog post')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search...'])
<main class="mt-16 p-8 min-h-screen pb-24">
<div class="max-w-7xl mx-auto">
<nav class="flex text-label-md text-on-surface-variant mb-4 gap-2">
<a class="hover:text-primary transition-colors" href="{{ route('admin.blog.index') }}">Blog</a>
<span>/</span>
<span class="text-on-surface font-medium">{{ $isEdit ? 'Edit' : 'Create' }}</span>
</nav>
<form method="post" action="{{ $isEdit ? route('admin.blog.update', $post) : route('admin.blog.store') }}" enctype="multipart/form-data" class="space-y-8">
@csrf
@if($isEdit)
@method('PUT')
@endif

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
<div class="lg:col-span-9 space-y-6">
<div class="rounded-2xl border border-outline-variant bg-white shadow-sm p-6 space-y-5">
<h2 class="text-lg font-bold text-on-surface border-b border-outline-variant pb-3">Content</h2>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Title <span class="text-error">*</span></label>
<input type="text" name="title" value="{{ old('title', $post?->title) }}" required class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"/>
@error('title')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">URL slug</label>
<input type="text" name="slug" value="{{ old('slug', $post?->slug) }}" placeholder="auto from title if empty" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-mono text-sm"/>
@error('slug')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Excerpt</label>
<textarea name="excerpt" rows="3" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none" placeholder="Short summary for listings">{{ old('excerpt', $post?->excerpt) }}</textarea>
@error('excerpt')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Body</label>
@include('admin.partials.rich-text-editor')
<textarea id="blog-body-editor" name="body" rows="18" data-admin-tinymce data-rich-height="520" class="w-full min-h-[360px] rounded-xl border border-outline-variant px-3 py-2 text-body-md">{!! old('body', $post?->body ?? '') !!}</textarea>
@error('body')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
</div>
</div>

<div class="lg:col-span-3 space-y-6 lg:sticky lg:top-24">
<div class="rounded-2xl border border-outline-variant bg-white shadow-sm p-6 space-y-4">
<h2 class="text-lg font-bold text-on-surface border-b border-outline-variant pb-3">Hero image</h2>
@if($isEdit && $post->hero_image_path)
<div class="rounded-xl overflow-hidden border border-outline-variant aspect-video bg-surface-container">
<img src="{{ asset('uploads/'.$post->hero_image_path) }}" alt="" class="w-full h-full object-cover"/>
</div>
@endif
<input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" class="block w-full text-body-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary/10 file:text-primary file:font-semibold"/>
@error('hero_image')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>

<div class="rounded-2xl border border-outline-variant bg-white shadow-sm p-6 space-y-5">
<h2 class="text-lg font-bold text-on-surface border-b border-outline-variant pb-3">SEO</h2>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Meta title</label>
<input type="text" name="meta_title" value="{{ old('meta_title', $post?->meta_title) }}" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"/>
@error('meta_title')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Meta description</label>
<textarea name="meta_description" rows="4" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none">{{ old('meta_description', $post?->meta_description) }}</textarea>
@error('meta_description')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
</div>

<div class="rounded-2xl border border-outline-variant bg-white shadow-sm p-6 space-y-5">
<h2 class="text-lg font-bold text-on-surface border-b border-outline-variant pb-3">Publishing</h2>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Status</label>
<select name="status" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
<option value="draft" @selected(old('status', $post?->status ?? 'draft') === 'draft')>Draft</option>
<option value="published" @selected(old('status', $post?->status) === 'published')>Published</option>
</select>
@error('status')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-1.5">Publish date</label>
<input type="datetime-local" name="published_at" value="{{ old('published_at', $post?->published_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"/>
@error('published_at')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
</div>
</div>
</div>
</div>

<div class="flex flex-wrap gap-3 justify-end pt-2 border-t border-outline-variant/60">
<a href="{{ route('admin.blog.index') }}" class="px-5 py-2.5 rounded-xl border border-outline-variant text-on-surface font-semibold hover:bg-surface-container-low">Cancel</a>
<button type="submit" class="px-6 py-2.5 rounded-xl bg-primary text-white font-bold hover:bg-primary-container shadow-md shadow-primary/20">{{ $isEdit ? 'Save changes' : 'Create post' }}</button>
</div>
</form>
</div>
</main>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ta = document.getElementById('blog-body-editor');
    if (ta && window.adminRichTextInit) window.adminRichTextInit(ta);
});
</script>
@endpush
@endsection
