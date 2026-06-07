@extends('admin.layouts.app')

@section('title', 'Blog')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search posts...'])
<main class="mt-16 p-8 min-h-screen pb-12">
<div class="max-w-6xl mx-auto">
<nav class="flex text-label-md text-on-surface-variant mb-4 gap-2">
<a class="hover:text-primary transition-colors" href="{{ route('admin.dashboard') }}">Dashboard</a>
<span>/</span>
<span class="text-on-surface font-medium">Blog</span>
</nav>
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
<div>
<h1 class="text-2xl font-bold text-on-surface">Blog posts</h1>
<p class="text-body-md text-on-surface-variant mt-1">Articles with hero image, excerpt, and SEO.</p>
</div>
<a href="{{ route('admin.blog.create') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-label-md hover:bg-primary-container shadow-md shadow-primary/20 shrink-0">
<span class="material-symbols-outlined text-[20px]">add</span>
New post
</a>
</div>
@if(session('success'))
<div class="mb-6 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md">{{ session('success') }}</div>
@endif
<div class="rounded-2xl border border-outline-variant bg-white shadow-sm overflow-hidden">
<table class="w-full text-left text-body-md">
<thead class="bg-surface-container-low/80 border-b border-outline-variant text-label-md font-semibold text-on-surface-variant uppercase tracking-wide text-[11px]">
<tr>
<th class="px-5 py-3">Title</th>
<th class="px-5 py-3 hidden md:table-cell">Slug</th>
<th class="px-5 py-3">Status</th>
<th class="px-5 py-3 text-right">Actions</th>
</tr>
</thead>
<tbody class="divide-y divide-outline-variant/60">
@forelse($posts as $post)
<tr class="hover:bg-surface-container-low/40">
<td class="px-5 py-4 font-medium text-on-surface">{{ $post->title }}</td>
<td class="px-5 py-4 text-on-surface-variant hidden md:table-cell tabular-nums text-sm">{{ $post->slug }}</td>
<td class="px-5 py-4">
@if($post->status === 'published')
<span class="inline-flex px-2 py-0.5 rounded-md text-label-md font-semibold bg-green-100 text-green-900">Published</span>
@else
<span class="inline-flex px-2 py-0.5 rounded-md text-label-md font-semibold bg-surface-container-high text-on-surface-variant">Draft</span>
@endif
</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.blog.edit', $post) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/5">
                                        <span class="material-symbols-outlined text-[16px]">edit_square</span>
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.blog.destroy', $post) }}" method="post" class="inline"
                                        onsubmit="return confirm('Delete this post?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-error/30 px-3 py-1.5 text-xs font-semibold text-error hover:bg-error-container/40">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </td>
</tr>
@empty
<tr>
<td colspan="4" class="px-5 py-12 text-center text-on-surface-variant">No posts yet.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
@if($posts->hasPages())
<div class="mt-6">{{ $posts->links() }}</div>
@endif
</div>
</main>
@endsection
