@extends('admin.layouts.app')

@section('title', 'Pages')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search pages...'])
<main class="mt-16 p-8 min-h-screen pb-12">
<div class="max-w-6xl mx-auto">
<nav class="flex text-label-md text-on-surface-variant mb-4 gap-2">
<a class="hover:text-primary transition-colors" href="{{ route('admin.dashboard') }}">Dashboard</a>
<span>/</span>
<span class="text-on-surface font-medium">Pages</span>
</nav>
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
<div>
<h1 class="text-2xl font-bold text-on-surface">Pages</h1>
<p class="text-body-md text-on-surface-variant mt-1">Static pages with hero image and SEO.</p>
</div>
<a href="{{ route('admin.pages.create') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white font-semibold text-label-md hover:bg-primary-container shadow-md shadow-primary/20 shrink-0">
<span class="material-symbols-outlined text-[20px]">add</span>
New page
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
@forelse($pages as $page)
<tr class="hover:bg-surface-container-low/40">
<td class="px-5 py-4 font-medium text-on-surface">{{ $page->title }}</td>
<td class="px-5 py-4 text-on-surface-variant hidden md:table-cell tabular-nums text-sm">{{ $page->slug }}</td>
<td class="px-5 py-4">
@if($page->status === 'published')
<span class="inline-flex px-2 py-0.5 rounded-md text-label-md font-semibold bg-green-100 text-green-900">Published</span>
@else
<span class="inline-flex px-2 py-0.5 rounded-md text-label-md font-semibold bg-surface-container-high text-on-surface-variant">Draft</span>
@endif
</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.pages.edit', $page) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/5">
                                        <span class="material-symbols-outlined text-[16px]">edit_square</span>
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.pages.destroy', $page) }}" method="post" class="inline"
                                        onsubmit="return confirm('Delete this page?');">
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
<td colspan="4" class="px-5 py-12 text-center text-on-surface-variant">No pages yet. Create one to get started.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
@if($pages->hasPages())
<div class="mt-6">{{ $pages->links() }}</div>
@endif
</div>
</main>
@endsection
