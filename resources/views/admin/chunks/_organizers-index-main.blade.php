@php
  $btnBase = 'px-4 py-2 rounded-lg text-body-md font-medium transition-colors';
  $btnActive = 'bg-primary-container text-on-primary-container';
  $btnIdle = 'hover:bg-surface-container text-on-surface-variant';
@endphp
<div class="max-w-7xl mx-auto space-y-8">
  @if(session('success'))
    <div
      class="rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-on-surface">
      {{ session('success') }}
    </div>
  @endif
  <!-- Header Section -->
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
      <h2 class="text-display-lg font-bold text-on-surface">Organizers</h2>
      <p class="text-body-lg text-on-surface-variant">Manage and monitor event partners within the Enterprise Console.
      </p>
    </div>
    <a href="{{ route('admin.organizers.create') }}"
      class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 shadow-sm hover:shadow-md transition-shadow active:scale-[0.98]">
      <span class="material-symbols-outlined">person_add</span>
      Add Organizer
    </a>
  </div>
  <!-- Filter & Stats Bar -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div class="bg-white p-5 rounded-xl border border-outline-variant flex items-center gap-4">
      <div class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center text-on-primary-fixed">
        <span class="material-symbols-outlined">group</span>
      </div>
      <div>
        <p class="text-label-md text-on-surface-variant font-medium">TOTAL ORGANIZERS</p>
        <p class="text-headline-lg font-bold">{{ number_format($totalOrganizers) }}</p>
      </div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-outline-variant flex items-center gap-4">
      <div class="w-12 h-12 rounded-full bg-secondary-fixed flex items-center justify-center text-on-secondary-fixed">
        <span class="material-symbols-outlined">check_circle</span>
      </div>
      <div>
        <p class="text-label-md text-on-surface-variant font-medium">ACTIVE PARTNERS</p>
        <p class="text-headline-lg font-bold">{{ number_format($activePartners) }}</p>
      </div>
    </div>
    <div class="md:col-span-2 bg-white p-5 rounded-xl border border-outline-variant">
      <form method="get" action="{{ route('admin.organizers.index') }}" class="flex flex-col gap-4">
        @if($statusFilter !== 'all')
          <input type="hidden" name="status" value="{{ $statusFilter }}" />
        @endif
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex gap-2 overflow-x-auto pb-1">
            <a href="{{ route('admin.organizers.index', array_filter(['q' => request('q')])) }}"
              class="{{ $btnBase }} {{ $statusFilter === 'all' ? $btnActive : $btnIdle }}">All Status</a>
            <a href="{{ route('admin.organizers.index', array_filter(['status' => 'active', 'q' => request('q')])) }}"
              class="{{ $btnBase }} {{ $statusFilter === 'active' ? $btnActive : $btnIdle }}">Active</a>
            <a href="{{ route('admin.organizers.index', array_filter(['status' => 'inactive', 'q' => request('q')])) }}"
              class="{{ $btnBase }} {{ $statusFilter === 'inactive' ? $btnActive : $btnIdle }}">Inactive</a>
          </div>
          <div class="flex items-center gap-2 w-full md:w-auto">
            <input name="q" value="{{ request('q') }}" type="search" placeholder="Search name, company, email..."
              class="flex-1 min-w-[12rem] px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright" />
            <button type="submit"
              class="flex items-center gap-2 px-4 py-2 border border-outline-variant rounded-lg text-body-md font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
              <span class="material-symbols-outlined text-sm">search</span>
              Search
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
  <!-- Data Table Section (Asymmetric / Modern Bento) -->
  <div class="bg-white rounded-xl border border-outline-variant overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-surface-container-low border-b border-outline-variant">
            <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant tracking-wider uppercase whitespace-nowrap">Name</th>
            <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant tracking-wider uppercase whitespace-nowrap">
              Company/Organization</th>
            <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant tracking-wider uppercase whitespace-nowrap">Events</th>
            <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant tracking-wider uppercase whitespace-nowrap">Status</th>
            <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant tracking-wider uppercase whitespace-nowrap">Panel access</th>
            <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant tracking-wider uppercase whitespace-nowrap">Contact email</th>
            <th class="w-20 px-3 py-4 text-label-md font-bold text-on-surface-variant tracking-wider uppercase text-center">
              Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-surface-container">
          @forelse($organizers as $organizer)
            <tr class="hover:bg-surface-container-lowest transition-colors">
              <td class="px-6 py-5">
                <div class="flex items-center gap-3">
                  <div
                    class="w-10 h-10 rounded-full bg-primary/5 flex items-center justify-center text-primary font-bold shrink-0">
                    {{ $organizer->initials() }}</div>
                  <div>
                    <p class="text-body-md font-semibold text-on-surface">{{ $organizer->formattedName() }}</p>
                    <p class="text-label-md text-on-surface-variant">{{ $organizer->job_title ?: '—' }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-5 text-body-md text-on-surface">{{ $organizer->company_name }}</td>
              <td class="px-6 py-5 text-body-md font-medium">{{ number_format($organizer->events_count) }}</td>
              <td class="px-6 py-5">
                @if($organizer->status === 'active')
                  <span
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#DCFCE7] text-[#166534]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#166534]"></span>
                    Active
                  </span>
                @else
                  <span
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-surface-container text-on-surface-variant">
                    <span class="w-1.5 h-1.5 rounded-full bg-outline"></span>
                    Inactive
                  </span>
                @endif
              </td>
              <td class="px-6 py-5">
                @if($organizer->adminRole)
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-primary-fixed/30 text-on-surface">
                    {{ $organizer->adminRole->name }}
                  </span>
                @elseif($organizer->hasPanelAccess())
                  <span class="text-label-md text-on-surface-variant">—</span>
                @else
                  <span class="text-label-md text-on-surface-variant">No panel access</span>
                @endif
              </td>
              <td class="px-6 py-5 text-body-md text-on-surface-variant min-w-[11rem] max-w-[16rem] break-all">{{ $organizer->email }}</td>
              <td class="w-20 px-3 py-5 text-center align-middle">
                <div class="relative flex justify-center">
                  <details class="organizer-row-actions relative inline-block text-left">
                    <summary
                      class="list-none cursor-pointer inline-flex items-center justify-center w-9 h-9 rounded-lg border border-outline-variant bg-white text-on-surface-variant hover:bg-surface-container-low hover:text-primary hover:border-primary/30 transition-all shadow-sm [&::-webkit-details-marker]:hidden active:scale-95"
                      aria-label="Organizer actions" title="Actions">
                      <span class="material-symbols-outlined text-[22px] leading-none">more_vert</span>
                    </summary>
                    <div class="absolute right-0 top-full mt-1.5 w-48 py-1.5 rounded-xl pointer-events-auto" style="z-index: 30;">
                      <div
                        class="rounded-xl border border-outline-variant bg-white shadow-[0_10px_40px_-10px_rgba(0,0,0,0.2)] overflow-hidden">
                        <a href="{{ route('admin.organizers.edit', $organizer) }}"
                          class="flex items-center gap-3 px-4 py-2.5 text-[13px] font-medium text-on-surface hover:bg-surface-container-low transition-colors">
                          <span class="material-symbols-outlined text-[20px] text-primary shrink-0">edit_square</span>
                          <span>Edit organizer</span>
                        </a>
                        <form action="{{ route('admin.organizers.destroy', $organizer) }}" method="post"
                          onsubmit="return confirm('Delete this organizer?');" class="border-t border-outline-variant/80">
                          @csrf
                          @method('DELETE')
                          <button type="submit"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-[13px] font-medium text-error hover:bg-error-container/40 transition-colors text-left">
                            <span class="material-symbols-outlined text-[20px] shrink-0">delete</span>
                            <span>Remove organizer</span>
                          </button>
                        </form>
                      </div>
                    </div>
                  </details>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-6 py-12 text-center text-body-md text-on-surface-variant">No organizers yet.
                Create one to get started.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <!-- Pagination -->
    @if($organizers->hasPages())
      <div class="px-6 py-4 bg-surface-container-low flex items-center justify-between border-t border-outline-variant">
        {{ $organizers->links('vendor.pagination.tailwind') }}
      </div>
    @elseif($organizers->total() > 0)
      <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant">
        <p class="text-label-md text-on-surface-variant">Showing {{ $organizers->total() }}
          {{ $organizers->total() === 1 ? 'entry' : 'entries' }}</p>
      </div>
    @endif
  </div>
  <!-- Secondary Content Area (Cards/Asymmetric) -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-outline-variant">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-headline-md font-bold text-on-surface">Top Performing Partners</h3>
        <span class="text-label-md text-on-surface-variant">By events hosted</span>
      </div>
      <div class="space-y-4">
        @forelse($topPartners as $idx => $partner)
          <div
            class="flex items-center justify-between p-4 bg-surface-container-lowest border border-outline-variant rounded-lg">
            <div class="flex items-center gap-4">
              <div class="w-2 h-10 {{ $idx === 0 ? 'bg-primary' : 'bg-secondary' }} rounded-full"></div>
              <div>
                <p class="text-body-md font-bold text-on-surface">{{ $partner->company_name }}</p>
                <p class="text-label-md text-on-surface-variant">{{ number_format($partner->events_count) }} events</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-body-md font-bold {{ $idx === 0 ? 'text-primary' : 'text-secondary' }}">{{ $partner->formattedName() }}
              </p>
              <p class="text-[10px] text-on-surface-variant uppercase tracking-tighter">Organizer</p>
            </div>
          </div>
        @empty
          <p class="text-body-md text-on-surface-variant">Add organizers and event counts to see rankings here.</p>
        @endforelse
      </div>
    </div>
    <div class="bg-primary text-white p-8 rounded-xl relative overflow-hidden flex flex-col justify-between">
      <div class="relative z-10">
        <span class="material-symbols-outlined text-4xl mb-4" data-weight="fill">verified</span>
        <h3 class="text-headline-md font-bold mb-2">Organizer Onboarding</h3>
        <p class="text-on-primary-container text-body-md mb-6 leading-relaxed">Streamline your partner growth with our
          new automated vetting and onboarding workflow module.</p>
        <a href="{{ route('admin.organizers.create') }}"
          class="inline-block bg-white text-primary px-6 py-2 rounded-lg font-bold text-label-md active:scale-95 transition-transform text-center">
          Get Started
        </a>
      </div>
      <!-- Abstract decorative element -->
      <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
    </div>
  </div>
</div>