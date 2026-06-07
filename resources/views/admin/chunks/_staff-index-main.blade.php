<div class="max-w-7xl mx-auto space-y-8">
  @if(session('success'))
    <div class="rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-on-surface">
      {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="rounded-xl border border-error/30 bg-error-container/20 px-4 py-3 text-body-md text-error">
      {{ session('error') }}
    </div>
  @endif

  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
      <h2 class="text-display-lg font-bold text-on-surface">Staff</h2>
      <p class="text-body-lg text-on-surface-variant">Manage admin users, assign roles, and control module access.</p>
    </div>
    <div class="flex flex-wrap gap-3">
      <a href="{{ route('admin.roles.index') }}"
        class="px-5 py-3 rounded-xl border border-outline-variant text-on-surface font-semibold flex items-center gap-2 hover:bg-surface-container-low transition-colors">
        <span class="material-symbols-outlined">shield_person</span>
        Roles
      </a>
      <a href="{{ route('admin.staff.create') }}"
        class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 shadow-sm hover:shadow-md transition-shadow">
        <span class="material-symbols-outlined">person_add</span>
        Add Staff
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white p-5 rounded-xl border border-outline-variant flex items-center gap-4">
      <div class="w-12 h-12 rounded-full bg-primary-fixed flex items-center justify-center text-on-primary-fixed">
        <span class="material-symbols-outlined">groups</span>
      </div>
      <div>
        <p class="text-label-md text-on-surface-variant font-medium">TOTAL STAFF</p>
        <p class="text-headline-lg font-bold">{{ number_format($totalStaff) }}</p>
      </div>
    </div>
    <div class="md:col-span-2 bg-white p-5 rounded-xl border border-outline-variant">
      <form method="get" action="{{ route('admin.staff.index') }}" class="flex items-center gap-2">
        <input name="q" value="{{ request('q') }}" type="search" placeholder="Search name or email..."
          class="flex-1 px-3 py-2 rounded-lg border border-outline-variant text-body-md bg-surface-bright" />
        <button type="submit"
          class="flex items-center gap-2 px-4 py-2 border border-outline-variant rounded-lg text-body-md font-medium hover:bg-surface-container-low">
          <span class="material-symbols-outlined text-sm">search</span>
          Search
        </button>
      </form>
    </div>
  </div>

  <div class="bg-white rounded-xl border border-outline-variant overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-surface-container-low border-b border-outline-variant">
            <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant uppercase">Name</th>
            <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant uppercase">Email</th>
            <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant uppercase">Role</th>
            <th class="px-6 py-4 text-label-md font-bold text-on-surface-variant uppercase text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-surface-container">
          @forelse($staffUsers as $member)
            <tr class="hover:bg-surface-container-lowest transition-colors">
              <td class="px-6 py-5">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold shrink-0">
                    {{ strtoupper(substr($member->name, 0, 1)) }}
                  </div>
                  <div>
                    <p class="text-body-md font-semibold text-on-surface">{{ $member->name }}</p>
                    @if($member->id === auth()->id())
                      <p class="text-label-md text-primary font-medium">You</p>
                    @endif
                  </div>
                </div>
              </td>
              <td class="px-6 py-5 text-body-md text-on-surface-variant font-mono">{{ $member->email }}</td>
              <td class="px-6 py-5">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-primary-fixed text-on-primary-fixed">
                  {{ $member->adminRole?->name ?? 'No role' }}
                </span>
              </td>
              <td class="px-6 py-5 text-right">
                <div class="flex justify-end items-center gap-1">
                  <a href="{{ route('admin.staff.edit', $member) }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/5">
                    <span class="material-symbols-outlined text-[16px]">edit_square</span>
                    Edit
                  </a>
                  @if($member->id !== auth()->id())
                    <form action="{{ route('admin.staff.destroy', $member) }}" method="post" class="inline-flex"
                      onsubmit="return confirm('Remove this staff member?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-error/30 px-3 py-1.5 text-xs font-semibold text-error hover:bg-error-container/40">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                        Remove
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="px-6 py-12 text-center text-body-md text-on-surface-variant">
                No staff members yet. Add one to delegate admin access.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($staffUsers->hasPages())
      <div class="px-6 py-4 border-t border-outline-variant">
        {{ $staffUsers->links() }}
      </div>
    @endif
  </div>
</div>
