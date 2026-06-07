@php
    $isEdit = isset($organizer) && $organizer instanceof \App\Models\Organizer;
    $o = $isEdit ? $organizer : null;
@endphp
<form class="space-y-8" method="post" action="{{ $isEdit ? route('admin.organizers.update', $o) : route('admin.organizers.store') }}" enctype="multipart/form-data">
@csrf
@if($isEdit)
@method('PUT')
@endif
<!-- Main Form Grid (Asymmetric) -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
<!-- Left Column: Image Upload -->
<div class="lg:col-span-4">
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 flex flex-col items-center text-center">
<h3 class="text-headline-md font-semibold mb-6 w-full text-left">Profile Photo</h3>
<div class="relative group">
<div class="w-48 h-48 rounded-full overflow-hidden bg-surface-container border-4 border-white shadow-md mb-6 flex items-center justify-center">
@if($isEdit && $o->photo_path)
<img alt="" class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity" src="{{ asset('uploads/'.$o->photo_path) }}"/>
@else
<img alt="" class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBELfRbjJOJPVe5Ch3PFHdpEiR-PFyqGm4L1q6kUvuj5mdCkQzWGZ9xjFyahLubH0dm_UXEyytux0h34zVN54X7gXXPrvQexja2Q0hY7YzxBD1n358m_9GPTnvkeKyETIrvPC9Y8SuSuwWFMjbbQuBeau0R4qwQv0Lmq4RR1YYPZzWUGQSCZFLb1S5wRplWh1tWrbAlBORHvSAzf-Z-nevIqSePxpftNpYkyzQYodyk0vHHh7hZZ_j9YnjVXo72QVyqJRsoHliBtIs"/>
@endif
</div>
<label class="absolute bottom-4 right-4 bg-primary text-white p-3 rounded-full shadow-lg hover:bg-primary-container transition-all active:scale-95 cursor-pointer" for="organizer_photo">
<span class="material-symbols-outlined" data-icon="photo_camera">photo_camera</span>
</label>
<input class="sr-only" id="organizer_photo" name="photo" type="file" accept="image/jpeg,image/png,image/gif,image/webp"/>
</div>
@error('photo')
<p class="text-error text-label-md mt-2">{{ $message }}</p>
@enderror
<p class="text-label-md text-on-surface-variant px-4">
                                Recommended: 800x800px. <br/> Supports JPG, PNG, or GIF.
                            </p>
</div>
</div>
<!-- Right Column: Details -->
<div class="lg:col-span-8 space-y-6">
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 shadow-sm">
<h3 class="text-headline-md font-semibold mb-8">Personal Information</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_name">Full Name</label>
<input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('name') border-error @enderror" id="organizer_name" name="name" placeholder="e.g. Jonathan Doe" type="text" value="{{ old('name', $o?->name) }}" required/>
@error('name')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_job_title">Job Title</label>
<input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('job_title') border-error @enderror" id="organizer_job_title" name="job_title" placeholder="e.g. Director" type="text" value="{{ old('job_title', $o?->job_title) }}"/>
@error('job_title')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_company">Organization Name</label>
<input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('company_name') border-error @enderror" id="organizer_company" name="company_name" placeholder="e.g. Acme Events Group" type="text" value="{{ old('company_name', $o?->company_name) }}" required/>
@error('company_name')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_email">Email Address</label>
<input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('email') border-error @enderror" id="organizer_email" name="email" placeholder="jonathan@organization.com" type="email" value="{{ old('email', $o?->email) }}" required autocomplete="off"/>
@error('email')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_phone">Phone Number</label>
<input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('phone') border-error @enderror" id="organizer_phone" name="phone" placeholder="+1 (555) 000-0000" type="tel" value="{{ old('phone', $o?->phone) }}"/>
@error('phone')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_password">Password @if($isEdit)<span class="normal-case font-normal text-on-surface-variant">(optional)</span>@endif</label>
<input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('password') border-error @enderror" id="organizer_password" name="password" placeholder="{{ $isEdit ? 'Leave blank to keep current password' : 'Create a secure password' }}" type="password" {{ $isEdit ? '' : 'required' }} autocomplete="new-password"/>
@error('password')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
<div class="md:col-span-2 space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_bio">Bio / Description</label>
<textarea class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('bio') border-error @enderror" id="organizer_bio" name="bio" placeholder="Provide a brief professional overview of the organizer and their specialization..." rows="5">{{ old('bio', $o?->bio) }}</textarea>
@error('bio')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
</div>
</div>
<!-- Location Details Section -->
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 shadow-sm">
<h3 class="text-headline-md font-semibold mb-8">Location Details</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_country">Country</label>
<select class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('country') border-error @enderror" id="organizer_country" name="country">
<option value="">Select Country</option>
@php
    $countries = ['US' => 'United States', 'GB' => 'United Kingdom', 'CA' => 'Canada', 'BD' => 'Bangladesh'];
@endphp
@foreach($countries as $code => $label)
<option value="{{ $code }}" @selected(old('country', $o?->country) === $code)>{{ $label }}</option>
@endforeach
</select>
@error('country')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_city">City</label>
<input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('city') border-error @enderror" id="organizer_city" name="city" placeholder="e.g. New York" type="text" value="{{ old('city', $o?->city) }}"/>
@error('city')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_state">State / Province</label>
<input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('state') border-error @enderror" id="organizer_state" name="state" placeholder="e.g. NY" type="text" value="{{ old('state', $o?->state) }}"/>
@error('state')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_postal">Zip / Postal Code</label>
<input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('postal_code') border-error @enderror" id="organizer_postal" name="postal_code" placeholder="e.g. 10001" type="text" value="{{ old('postal_code', $o?->postal_code) }}"/>
@error('postal_code')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
<!-- Geographic Coordinates -->
<div class="md:col-span-2 pt-4">
<p class="text-label-lg font-bold text-on-surface-variant uppercase tracking-widest mb-4">Geographic Coordinates</p>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_lat">Latitude</label>
<input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('latitude') border-error @enderror" id="organizer_lat" name="latitude" placeholder="e.g. 40.7128" type="text" value="{{ old('latitude', $o?->latitude) }}"/>
@error('latitude')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_lng">Longitude</label>
<input class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright @error('longitude') border-error @enderror" id="organizer_lng" name="longitude" placeholder="e.g. -74.0060" type="text" value="{{ old('longitude', $o?->longitude) }}"/>
@error('longitude')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
</div>
</div>
</div>
</div>
<!-- Organization Branding (Additional Section for Visual Interest) -->
<div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 shadow-sm">
<div class="flex items-center justify-between mb-8">
<h3 class="text-headline-md font-semibold">Account Settings</h3>
<span class="px-3 py-1 bg-secondary-container text-on-secondary-container rounded-full text-label-md font-bold uppercase tracking-wide">Enterprise Tier</span>
</div>
<div class="flex flex-col gap-6">
@php
    $statusVal = old('status', $o?->status ?? 'active');
@endphp
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface uppercase tracking-wider" for="organizer_status">Account status</label>
<select class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-body-md bg-surface-bright" id="organizer_status" name="status">
<option value="active" @selected($statusVal === 'active')>Active</option>
<option value="inactive" @selected($statusVal === 'inactive')>Inactive</option>
</select>
@error('status')
<p class="text-error text-label-md">{{ $message }}</p>
@enderror
</div>
@php
    $panelAccess = filter_var(old('admin_panel_access', $o?->hasPanelAccess() ?? false), FILTER_VALIDATE_BOOLEAN);
    $organizerRoles = $roles ?? collect();
@endphp
<div class="mb-6 p-6 rounded-xl border border-primary/20 bg-primary/5 space-y-4">
<h4 class="text-headline-md font-semibold text-on-surface">Admin panel access</h4>
<p class="text-body-md text-on-surface-variant">Allow this organizer to sign in at <span class="font-mono text-sm">/admin/login</span> with a role that controls which modules they see.</p>
<div class="flex items-center gap-3">
<input type="hidden" name="admin_panel_access" value="0"/>
<label class="relative inline-flex cursor-pointer items-center">
<input type="checkbox" name="admin_panel_access" value="1" class="peer sr-only" id="organizer_panel_access" @checked($panelAccess)/>
<span class="peer h-6 w-12 rounded-full bg-surface-container-highest after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:bg-primary peer-checked:after:translate-x-6 relative"></span>
</label>
<span class="text-body-md font-medium">Enable admin panel login</span>
</div>
<div id="organizer_role_fields" class="grid grid-cols-1 md:grid-cols-2 gap-4 {{ $panelAccess ? '' : 'opacity-50 pointer-events-none' }}">
<div class="space-y-2">
<label class="block text-label-md font-bold uppercase tracking-wider" for="organizer_admin_role">Role</label>
<select class="w-full px-4 py-3 rounded-lg border border-outline-variant text-body-md bg-surface-bright @error('admin_role_id') border-error @enderror" id="organizer_admin_role" name="admin_role_id" {{ $panelAccess ? '' : 'disabled' }}>
<option value="">Select role</option>
@foreach($organizerRoles as $role)
<option value="{{ $role->id }}" @selected((int) old('admin_role_id', $o?->admin_role_id) === $role->id)>{{ $role->name }}</option>
@endforeach
</select>
@error('admin_role_id')<p class="text-error text-label-md">{{ $message }}</p>@enderror
<p class="text-label-md text-on-surface-variant"><a href="{{ route('admin.roles.create') }}" class="text-primary hover:underline">Create a role</a> with organizer or both audience.</p>
</div>
</div>
</div>
<script>
document.getElementById('organizer_panel_access')?.addEventListener('change', function () {
  const fields = document.getElementById('organizer_role_fields');
  if (!fields) return;
  const on = this.checked;
  fields.classList.toggle('opacity-50', !on);
  fields.classList.toggle('pointer-events-none', !on);
  fields.querySelectorAll('select, input').forEach(el => { el.disabled = !on; });
});
</script>
<div class="flex flex-col md:flex-row gap-6">
<div class="flex-1 space-y-4">
<div class="flex items-center gap-3 p-4 bg-surface-container-low rounded-lg border border-outline-variant/30">
<span class="material-symbols-outlined text-primary" data-icon="verified_user">verified_user</span>
<div>
<p class="text-body-md font-bold">Auto-approve Events</p>
<p class="text-label-md text-on-surface-variant">Allow this organizer to skip moderation.</p>
</div>
<div class="ml-auto flex items-center">
<input type="hidden" name="auto_approve_events" value="0"/>
<label class="relative inline-flex cursor-pointer items-center">
<input type="checkbox" name="auto_approve_events" value="1" class="peer sr-only" @checked(filter_var(old('auto_approve_events', $o?->auto_approve_events ?? false), FILTER_VALIDATE_BOOLEAN))/>
<span class="peer h-6 w-12 rounded-full bg-surface-container-highest after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:bg-primary peer-checked:after:translate-x-6 relative"></span>
</label>
</div>
</div>
<div class="flex items-center gap-3 p-4 bg-surface-container-low rounded-lg border border-outline-variant/30">
<span class="material-symbols-outlined text-primary" data-icon="mail">mail</span>
<div>
<p class="text-body-md font-bold">Digest Notifications</p>
<p class="text-label-md text-on-surface-variant">Send weekly summary of event performance.</p>
</div>
<div class="ml-auto flex items-center">
<input type="hidden" name="digest_notifications" value="0"/>
<label class="relative inline-flex cursor-pointer items-center">
<input type="checkbox" name="digest_notifications" value="1" class="peer sr-only" @checked(filter_var(old('digest_notifications', $o?->digest_notifications ?? true), FILTER_VALIDATE_BOOLEAN))/>
<span class="peer h-6 w-12 rounded-full bg-surface-container-highest after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:bg-primary peer-checked:after:translate-x-6 relative"></span>
</label>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
<!-- Sticky Action Footer -->
<div class="fixed bottom-0 right-0 left-sidebar-width bg-white border-t border-outline-variant px-8 py-4 flex justify-between items-center z-40">
<div class="hidden md:block">
<p class="text-label-md text-on-surface-variant">Unsaved changes will be lost.</p>
</div>
<div class="flex items-center gap-4">
<a href="{{ route('admin.organizers.index') }}" class="px-6 py-2.5 rounded-lg border border-outline-variant text-on-surface-variant font-semibold hover:bg-surface-container transition-colors inline-flex items-center justify-center">
                            Cancel
                        </a>
<button type="submit" class="px-8 py-2.5 rounded-lg bg-primary text-white font-bold hover:bg-primary-container shadow-md active:scale-95 transition-all inline-flex items-center justify-center border-0 cursor-pointer">
                            {{ $isEdit ? 'Save changes' : 'Create Organizer' }}
                        </button>
</div>
</div>
</form>
<!-- Spacer for footer -->
<div class="h-28" aria-hidden="true"></div>
