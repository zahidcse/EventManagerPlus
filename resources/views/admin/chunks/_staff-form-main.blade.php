@php
    $isEdit = $staffUser instanceof \App\Models\User;
    $u = $isEdit ? $staffUser : null;
@endphp
<div class="max-w-3xl mx-auto p-8">
  <div class="mb-8">
    <nav class="flex text-label-md text-on-surface-variant mb-2 gap-2">
      <a class="hover:text-primary transition-colors" href="{{ route('admin.staff.index') }}">Staff</a>
      <span class="material-symbols-outlined text-[14px]">chevron_right</span>
      <span class="text-primary font-semibold">{{ $isEdit ? 'Edit' : 'Add' }} Staff</span>
    </nav>
    <h2 class="text-display-lg font-bold text-on-surface">{{ $isEdit ? 'Edit Staff Member' : 'Add Staff Member' }}</h2>
    <p class="text-body-lg text-on-surface-variant mt-1">Staff can sign in at the admin login with the role you assign.</p>
  </div>

  @if(session('error'))
    <div class="mb-6 rounded-xl border border-error/30 bg-error-container/20 px-4 py-3 text-body-md text-error">
      {{ session('error') }}
    </div>
  @endif

  <form class="bg-white border border-outline-variant rounded-xl p-8 shadow-sm space-y-6" method="post"
    action="{{ $isEdit ? route('admin.staff.update', $u) : route('admin.staff.store') }}">
    @csrf
    @if($isEdit)
      @method('PUT')
    @endif

    <div class="space-y-2">
      <label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="staff_name">Full name</label>
      <input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 text-body-md bg-surface-bright @error('name') border-error @enderror"
        id="staff_name" name="name" type="text" value="{{ old('name', $u?->name) }}" required />
      @error('name')<p class="text-error text-label-md">{{ $message }}</p>@enderror
    </div>

    <div class="space-y-2">
      <label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="staff_email">Email</label>
      <input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 text-body-md bg-surface-bright @error('email') border-error @enderror"
        id="staff_email" name="email" type="email" value="{{ old('email', $u?->email) }}" required />
      @error('email')<p class="text-error text-label-md">{{ $message }}</p>@enderror
    </div>

    <div class="space-y-2">
      <label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="staff_password">
        Password{{ $isEdit ? ' (leave blank to keep current)' : '' }}
      </label>
      <input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 text-body-md bg-surface-bright @error('password') border-error @enderror"
        id="staff_password" name="password" type="password" {{ $isEdit ? '' : 'required' }} autocomplete="new-password" />
      @error('password')<p class="text-error text-label-md">{{ $message }}</p>@enderror
    </div>

    <div class="space-y-2">
      <label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="staff_role">Role</label>
      <select class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary text-body-md bg-surface-bright @error('admin_role_id') border-error @enderror"
        id="staff_role" name="admin_role_id" required>
        <option value="">Select a role</option>
        @foreach($roles as $role)
          <option value="{{ $role->id }}" @selected((int) old('admin_role_id', $u?->admin_role_id) === $role->id)>
            {{ $role->name }}{{ $role->is_super ? ' (full access)' : '' }}
          </option>
        @endforeach
      </select>
      @error('admin_role_id')<p class="text-error text-label-md">{{ $message }}</p>@enderror
      <p class="text-label-md text-on-surface-variant">
        <a href="{{ route('admin.roles.index') }}" class="text-primary hover:underline">Manage roles & module access</a>
      </p>
    </div>

    <div class="flex items-center gap-4 pt-4">
      <button type="submit"
        class="bg-primary text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition-opacity">
        {{ $isEdit ? 'Save changes' : 'Create staff member' }}
      </button>
      <a href="{{ route('admin.staff.index') }}" class="text-body-md text-on-surface-variant hover:text-primary">Cancel</a>
    </div>
  </form>
</div>
