@extends('admin.layouts.app')

@section('title', 'Event categories')

@section('content')
    @include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search events...'])
    <div class="mt-16 p-8 max-w-5xl pb-16 space-y-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-label-md font-semibold text-on-surface-variant tracking-wide uppercase text-[11px] mb-1">
                    Event setup</p>
                <h2 class="text-[28px] font-semibold tracking-tight text-on-surface">Manage categories</h2>
                <p class="text-on-surface-variant mt-1 max-w-2xl">Categories appear in the event create/edit wizard. Remove is only available when no events use a category.</p>
            </div>
            <div class="rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 shadow-sm shrink-0">
                <p class="text-label-md uppercase tracking-wider text-outline">Total categories</p>
                <p class="text-2xl font-bold text-on-surface tabular-nums">{{ number_format($categories->count()) }}</p>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[20px]">check_circle</span>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-error/40 bg-error/5 px-4 py-3 text-body-sm text-on-surface">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white border border-outline-variant rounded-2xl p-6 shadow-sm">
            <div class="flex items-center gap-3 mb-4">
                <span class="admin-icon-tile admin-icon-tile--lg h-10 w-10 rounded-xl bg-primary-fixed text-primary" aria-hidden="true">
                    <span class="material-symbols-outlined">category</span>
                </span>
                <div class="min-w-0">
                    <h3 class="font-bold text-on-surface">Add category</h3>
                    <p class="text-sm text-on-surface-variant">Create a reusable label for upcoming events.</p>
                </div>
            </div>
            <form method="post" action="{{ route('admin.event-categories.store') }}" class="flex flex-col sm:flex-row sm:items-stretch gap-3">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="120"
                    placeholder="e.g. Technology &amp; Innovation"
                    class="flex-1 min-w-0 px-4 py-3 border border-outline-variant rounded-xl text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 @error('name') border-error @enderror" />
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-primary text-white text-sm font-bold hover:bg-primary-container shrink-0 shadow-sm active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[20px]">add</span>
                    Add category
                </button>
            </form>
            @error('name')
                <p class="text-error text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="bg-white border border-outline-variant rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-surface-container-low border-b border-outline-variant">
                        <tr>
                            <th class="px-6 py-4 text-label-md uppercase tracking-wider font-semibold text-outline align-middle">Name</th>
                            <th class="px-6 py-4 text-label-md uppercase tracking-wider font-semibold text-outline align-middle text-center w-28">Events</th>
                            <th class="px-6 py-4 text-right text-label-md uppercase tracking-wider font-semibold text-outline align-middle min-w-[220px] w-[220px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($categories as $cat)
                            <tr class="hover:bg-surface-container-low/50">
                                <td class="px-6 py-4 align-middle">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="admin-icon-tile h-9 w-9 rounded-lg bg-primary-fixed text-primary" aria-hidden="true">
                                            <span class="material-symbols-outlined">label</span>
                                        </span>
                                        <span class="font-semibold text-on-surface truncate">{{ $cat->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-middle text-center">
                                    <span class="inline-flex min-w-[2.5rem] justify-center rounded-full bg-surface-container-low px-3 py-1 text-sm font-semibold text-on-surface tabular-nums">{{ number_format($cat->events_count) }}</span>
                                </td>
                                <td class="px-6 py-4 align-middle text-right">
                                    <div class="inline-flex flex-nowrap items-center justify-end gap-2">
                                        <a href="{{ route('admin.event-categories.edit', $cat) }}"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-outline-variant bg-white px-3 py-2 text-xs font-semibold text-primary hover:bg-primary/5 whitespace-nowrap">
                                            <span class="material-symbols-outlined text-[16px]">edit_square</span>
                                            Edit
                                        </a>
                                        @if($cat->events_count === 0)
                                            <form method="post" action="{{ route('admin.event-categories.destroy', $cat) }}"
                                                class="inline-flex shrink-0"
                                                onsubmit="return confirm('Remove this category?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-error/40 bg-white px-3 py-2 text-xs font-semibold text-error hover:bg-error-container/30 whitespace-nowrap">
                                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                                    Remove
                                                </button>
                                            </form>
                                        @else
                                            <span
                                                class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-outline-variant bg-surface-container-high px-3 py-2 text-xs font-semibold text-on-surface-variant whitespace-nowrap"
                                                title="Cannot remove while events use this category">
                                                <span class="material-symbols-outlined text-[16px]">lock</span>
                                                In use
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-on-surface-variant">No categories yet. Add one above to organize events.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
