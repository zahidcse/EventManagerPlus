@extends('admin.layouts.app')

@section('title', 'Your profile')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search…'])
<div class="pt-16 pb-24">
<div class="max-w-2xl mx-auto p-8">
<nav class="flex text-label-md text-on-surface-variant mb-4 gap-2">
<a class="hover:text-primary transition-colors" href="{{ route('admin.dashboard') }}">Dashboard</a>
<span>/</span>
<span class="text-on-surface font-medium">Profile</span>
</nav>
<h1 class="text-2xl font-bold text-on-surface">Your profile</h1>
<p class="text-body-md text-on-surface-variant mt-1">Update your display name, email, or password.</p>

@if(session('success'))
<div class="mt-6 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-3 text-body-md text-on-surface">{{ session('success') }}</div>
@endif

@if ($errors->any())
<div class="mt-6 rounded-xl border border-error/40 bg-error-container/30 px-4 py-3 text-body-md text-on-error-container" role="alert">
<ul class="list-disc pl-5 space-y-1 m-0">
@foreach ($errors->all() as $msg)
<li>{{ $msg }}</li>
@endforeach
</ul>
</div>
@endif

<form method="post" action="{{ route('admin.profile.update') }}" class="mt-8 space-y-6 rounded-2xl border border-outline-variant bg-white dark:bg-[#2b2930] p-6 md:p-8 shadow-sm">
@csrf
@method('put')
<div>
<label class="block text-label-md font-semibold text-on-surface mb-2">Name</label>
<input type="text" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25 bg-surface-container-lowest dark:bg-transparent"/>
</div>
<div>
<label class="block text-label-md font-semibold text-on-surface mb-2">Email</label>
<input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25 bg-surface-container-lowest dark:bg-transparent"/>
</div>
<div class="pt-2 border-t border-outline-variant">
<p class="text-sm font-semibold text-on-surface mb-4">Change password <span class="font-normal text-on-surface-variant">(optional)</span></p>
<div class="space-y-4">
<div>
<label class="block text-label-md font-medium text-on-surface-variant mb-2 text-xs uppercase tracking-wide">Current password</label>
<input type="password" name="current_password" autocomplete="current-password" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25 bg-surface-container-lowest dark:bg-transparent"/>
</div>
<div>
<label class="block text-label-md font-medium text-on-surface-variant mb-2 text-xs uppercase tracking-wide">New password</label>
<input type="password" name="password" autocomplete="new-password" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25 bg-surface-container-lowest dark:bg-transparent"/>
</div>
<div>
<label class="block text-label-md font-medium text-on-surface-variant mb-2 text-xs uppercase tracking-wide">Confirm new password</label>
<input type="password" name="password_confirmation" autocomplete="new-password" class="w-full px-4 py-2.5 border border-outline-variant rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary/25 bg-surface-container-lowest dark:bg-transparent"/>
</div>
</div>
</div>
<div class="pt-2">
<button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-primary-container text-white font-semibold text-sm hover:opacity-90 transition-opacity">
<span class="material-symbols-outlined text-[20px]">save</span>
Save changes
</button>
</div>
</form>
</div>
</div>
@endsection
