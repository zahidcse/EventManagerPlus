<div class="max-w-6xl mx-auto">
    @if(session('success'))
        <div class="mb-6 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md">
            {{ session('success') }}</div>
    @endif
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
        <div>
            <nav class="flex text-label-md text-on-surface-variant mb-2 gap-2">
                <a class="hover:text-primary transition-colors" href="{{ route('admin.events.index') }}">Events</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="text-primary font-semibold">Speakers</span>
            </nav>
            <h2 class="text-2xl font-bold text-on-surface">Speakers</h2>
            <p class="text-body-md text-on-surface-variant mt-1">Directory of people you can attach to events (keynotes,
                hosts, panelists).</p>
        </div>
        <a href="{{ route('admin.speakers.create') }}"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-white font-bold rounded-xl hover:opacity-90 shadow-md shadow-primary/20 shrink-0">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Add speaker
        </a>
    </div>
    <div class="bg-white border border-outline-variant rounded-xl shadow-sm overflow-hidden">
        @if($speakers->isEmpty())
            <p class="p-10 text-center text-on-surface-variant text-body-md">No speakers yet. Add your first profile to use
                on event lineups.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-6 py-3 font-semibold text-on-surface">Photo</th>
                            <th class="px-6 py-3 font-semibold text-on-surface">Name</th>
                            <th class="px-6 py-3 font-semibold text-on-surface">Headline</th>
                            <th class="px-6 py-3 font-semibold text-on-surface text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @foreach($speakers as $sp)
                            <tr class="hover:bg-surface-container-low/40">
                                <td class="px-6 py-3">
                                    @if($sp->photo_path)
                                        <img src="{{ $sp->photoUrl() }}" alt=""
                                            class="w-10 h-10 rounded-full object-cover border border-outline-variant" />
                                    @else
                                        <div
                                            class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface-variant">
                                            <span class="material-symbols-outlined text-[20px]">person</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-3 font-medium text-on-surface">{{ $sp->name }}</td>
                                <td class="px-6 py-3 text-on-surface-variant">{{ $sp->headline ?? '—' }}</td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <a href="{{ route('admin.speakers.edit', $sp) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/5">
                                            <span class="material-symbols-outlined text-[16px]">edit_square</span>
                                            Edit
                                        </a>
                                        <form method="post" action="{{ route('admin.speakers.destroy', $sp) }}" class="inline"
                                            onsubmit="return confirm('Delete this speaker? They will be removed from all events.');">
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
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>