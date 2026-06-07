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
      <h2 class="text-display-lg font-bold text-on-surface">Roles & permissions</h2>
      <p class="text-body-lg text-on-surface-variant">Define which admin modules each role can access.</p>
    </div>
    <div class="flex flex-wrap gap-3">
      <a href="{{ route('admin.staff.index') }}"
        class="px-5 py-3 rounded-xl border border-outline-variant text-on-surface font-semibold flex items-center gap-2 hover:bg-surface-container-low">
        <span class="material-symbols-outlined">groups</span>
        Staff
      </a>
      <a href="{{ route('admin.roles.create') }}"
        class="bg-primary text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 shadow-sm hover:shadow-md">
        <span class="material-symbols-outlined">add_moderator</span>
        Create role
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    @foreach($roles as $role)
      <div class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm flex flex-col">
        <div class="flex items-start justify-between gap-3 mb-3">
          <div>
            <h3 class="text-headline-md font-bold text-on-surface">{{ $role->name }}</h3>
            <p class="text-label-md text-on-surface-variant font-mono">{{ $role->slug }}</p>
            <p class="text-label-md text-on-surface-variant mt-1">{{ $role->audienceLabel() }}</p>
          </div>
          @if($role->is_super)
            <span class="shrink-0 px-2 py-1 rounded text-xs font-bold bg-primary-fixed text-on-primary-fixed">Super</span>
          @endif
        </div>
        @if($role->description)
          <p class="text-body-md text-on-surface-variant mb-4">{{ $role->description }}</p>
        @endif
        <p class="text-label-md text-on-surface-variant mb-2">
          {{ $role->users_count }} staff · {{ $role->organizers_count }} organizers ·
          {{ $role->is_super ? count($moduleDefinitions) : count($role->permissionKeys()) }} modules
        </p>
        <div class="flex flex-wrap gap-1 mb-6 min-h-[2rem]">
          @foreach(array_slice($role->is_super ? array_keys($moduleDefinitions) : $role->permissionKeys(), 0, 6) as $key)
            <span class="inline-flex items-center justify-center leading-none text-[10px] px-2 py-1 rounded-full bg-surface-container text-on-surface-variant">
              {{ $moduleDefinitions[$key]['label'] ?? $key }}
            </span>
          @endforeach
          @php $extra = ($role->is_super ? count($moduleDefinitions) : count($role->permissionKeys())) - 6; @endphp
          @if($extra > 0)
            <span class="inline-flex items-center justify-center leading-none text-[10px] px-2 py-1 rounded-full bg-surface-container text-on-surface-variant">+{{ $extra }}</span>
          @endif
        </div>
        <div class="mt-auto flex gap-2">
          @if(!$role->is_super)
            <a href="{{ route('admin.roles.edit', $role) }}"
              class="flex-1 text-center py-2 rounded-lg border border-outline-variant text-sm font-semibold text-primary hover:bg-primary/5">
              Edit
            </a>
            <form action="{{ route('admin.roles.destroy', $role) }}" method="post" class="flex-1"
              onsubmit="return confirm('Delete this role?');">
              @csrf
              @method('DELETE')
              <button type="submit"
                class="w-full py-2 rounded-lg border border-error/30 text-sm font-semibold text-error hover:bg-error-container/40"
                {{ $role->users_count > 0 ? 'disabled title=Role is in use' : '' }}>
                Delete
              </button>
            </form>
          @else
            <p class="text-label-md text-on-surface-variant italic">Built-in role (not editable)</p>
          @endif
        </div>
      </div>
    @endforeach
  </div>
</div>
