@php
    $isEdit = $role instanceof \App\Models\AdminRole;
    $r = $isEdit ? $role : null;
    $selected = old('permissions', $r?->permissions ?? []);
    $audience = old('audience', $r?->audience ?? 'both');
    $moduleDefinitions = \App\Support\Admin\AdminModules::definitions();
@endphp
<div class="max-w-5xl mx-auto p-8">
  <div class="mb-8">
    <nav class="flex text-label-md text-on-surface-variant mb-2 gap-2">
      <a class="hover:text-primary" href="{{ route('admin.roles.index') }}">Roles</a>
      <span class="material-symbols-outlined text-[14px]">chevron_right</span>
      <span class="text-primary font-semibold">{{ $isEdit ? 'Edit' : 'Create' }} role</span>
    </nav>
    <h2 class="text-display-lg font-bold text-on-surface">{{ $isEdit ? 'Edit role' : 'Create role' }}</h2>
    <p class="text-body-lg text-on-surface-variant mt-1">Assign module keys to control what appears in the admin sidebar.</p>
  </div>

  <form class="space-y-8" method="post"
    action="{{ $isEdit ? route('admin.roles.update', $r) : route('admin.roles.store') }}">
    @csrf
    @if($isEdit)
      @method('PUT')
    @endif

    <div class="bg-white border border-outline-variant rounded-xl p-8 shadow-sm space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <label class="block text-label-md font-bold uppercase tracking-wider" for="role_name">Role name</label>
          <input class="w-full px-4 py-3 rounded-lg border border-outline-variant text-body-md bg-surface-bright @error('name') border-error @enderror"
            id="role_name" name="name" type="text" value="{{ old('name', $r?->name) }}" required />
          @error('name')<p class="text-error text-label-md">{{ $message }}</p>@enderror
        </div>
        <div class="space-y-2">
          <label class="block text-label-md font-bold uppercase tracking-wider" for="role_slug">Slug</label>
          <input class="w-full px-4 py-3 rounded-lg border border-outline-variant text-body-md bg-surface-bright font-mono @error('slug') border-error @enderror"
            id="role_slug" name="slug" type="text" value="{{ old('slug', $r?->slug) }}" required />
          @error('slug')<p class="text-error text-label-md">{{ $message }}</p>@enderror
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <label class="block text-label-md font-bold uppercase tracking-wider" for="role_audience">Used for</label>
          <select class="w-full px-4 py-3 rounded-lg border border-outline-variant text-body-md bg-surface-bright @error('audience') border-error @enderror"
            id="role_audience" name="audience" required>
            <option value="both" @selected($audience === 'both')>Staff & organizers</option>
            <option value="staff" @selected($audience === 'staff')>Staff only</option>
            <option value="organizer" @selected($audience === 'organizer')>Organizers only</option>
          </select>
          @error('audience')<p class="text-error text-label-md">{{ $message }}</p>@enderror
        </div>
        <div class="space-y-2">
          <label class="block text-label-md font-bold uppercase tracking-wider" for="role_description">Description</label>
          <input class="w-full px-4 py-3 rounded-lg border border-outline-variant text-body-md bg-surface-bright @error('description') border-error @enderror"
            id="role_description" name="description" type="text" value="{{ old('description', $r?->description) }}" />
          @error('description')<p class="text-error text-label-md">{{ $message }}</p>@enderror
        </div>
      </div>
    </div>

    <div class="bg-white border border-outline-variant rounded-xl p-8 shadow-sm overflow-hidden">
      <h3 class="text-headline-md font-semibold mb-2">Module reference</h3>
      <p class="text-body-md text-on-surface-variant mb-4">Each permission key maps to an admin area:</p>
      <div class="overflow-x-auto rounded-lg border border-outline-variant mb-8">
        <table class="w-full text-left text-sm">
          <thead class="bg-surface-container-low">
            <tr>
              <th class="px-4 py-2 font-bold text-on-surface-variant uppercase text-xs">Module key</th>
              <th class="px-4 py-2 font-bold text-on-surface-variant uppercase text-xs">Admin area</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/50">
            @foreach($moduleDefinitions as $key => $def)
              @if($key !== 'staff')
                <tr>
                  <td class="px-4 py-2 font-mono text-primary">{{ $key }}</td>
                  <td class="px-4 py-2 text-on-surface">{{ $def['label'] }}</td>
                </tr>
              @endif
            @endforeach
            <tr>
              <td class="px-4 py-2 font-mono text-primary">staff</td>
              <td class="px-4 py-2 text-on-surface">Team & roles (staff accounts only)</td>
            </tr>
          </tbody>
        </table>
      </div>

      <h3 class="text-headline-md font-semibold mb-4">Select module access</h3>
      @foreach($groupedModules as $groupName => $modules)
        <div class="mb-8 last:mb-0">
          <p class="text-label-md font-bold text-on-surface-variant uppercase tracking-wider mb-3">{{ $groupName }}</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($modules as $key => $definition)
              @if($key === 'staff')
                @continue
              @endif
              <label class="flex items-start gap-3 p-4 rounded-lg border border-outline-variant hover:bg-surface-container-lowest cursor-pointer has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                <input type="checkbox" name="permissions[]" value="{{ $key }}"
                  class="mt-1 rounded border-outline-variant text-primary focus:ring-primary"
                  @checked(in_array($key, $selected, true)) />
                <span>
                  <span class="block text-body-md font-semibold text-on-surface">{{ $definition['label'] }}</span>
                  <span class="block text-label-md font-mono text-primary/80">{{ $key }}</span>
                  <span class="block text-label-md text-on-surface-variant">{{ $definition['description'] }}</span>
                </span>
              </label>
            @endforeach
          </div>
        </div>
      @endforeach

      <div class="p-4 rounded-lg bg-surface-container-low border border-outline-variant" id="staff_permission_block">
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="checkbox" name="permissions[]" value="staff" id="perm_staff"
            class="mt-1 rounded border-outline-variant text-primary focus:ring-primary"
            @checked(in_array('staff', $selected, true)) />
          <span>
            <span class="block text-body-md font-semibold text-on-surface">Team & roles</span>
            <span class="block text-label-md font-mono text-primary/80">staff</span>
            <span class="block text-label-md text-on-surface-variant">Manage staff accounts and roles (staff audience only).</span>
          </span>
        </label>
      </div>
      @error('permissions')<p class="text-error text-label-md mt-2">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center gap-4">
      <button type="submit" class="bg-primary text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90">
        {{ $isEdit ? 'Save role' : 'Create role' }}
      </button>
      <a href="{{ route('admin.roles.index') }}" class="text-body-md text-on-surface-variant hover:text-primary">Cancel</a>
    </div>
  </form>
</div>
<script>
(function () {
  const audience = document.getElementById('role_audience');
  const staffBlock = document.getElementById('staff_permission_block');
  const staffCheck = document.getElementById('perm_staff');
  function syncStaffPerm() {
    if (!audience || !staffBlock) return;
    const orgOnly = audience.value === 'organizer';
    staffBlock.classList.toggle('hidden', orgOnly);
    if (orgOnly && staffCheck) staffCheck.checked = false;
  }
  audience?.addEventListener('change', syncStaffPerm);
  syncStaffPerm();
})();
</script>
