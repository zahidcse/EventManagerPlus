@extends('admin.layouts.guest')

@section('title')

Login · {{ $siteDisplayName }}

@endsection

@section('content')
<main class="w-full max-w-[440px] space-y-8">
<div class="text-center">
<div class="flex flex-col items-center justify-center gap-3 mb-2">
@if (! empty($siteLogoUrl))
<img src="{{ $siteLogoUrl }}" alt="" class="max-h-20 max-w-[280px] w-auto object-contain" width="280" height="80" decoding="async" />
@else
<span class="material-symbols-outlined text-primary text-4xl" aria-hidden="true">hub</span>
@endif
<span class="text-[28px] font-bold text-primary tracking-tight leading-tight text-balance">{{ $siteDisplayName }}</span>
</div>
<h1 class="text-on-surface font-semibold text-xl leading-tight">Administrator sign-in</h1>
<p class="text-on-surface-variant text-sm mt-2">Use your administrator account.</p>
</div>
<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl shadow-sm overflow-hidden">
<form method="post" action="{{ route('admin.login.submit') }}" class="p-8 space-y-6">
@csrf
@if($errors->any())
<div class="rounded-lg border border-error/40 bg-error-container/30 px-4 py-3 text-sm text-error">
{{ $errors->first('email') ?: $errors->first() }}
</div>
@endif
<div class="space-y-1.5">
<label class="block text-sm font-medium text-on-surface-variant" for="email">Email Address</label>
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">mail</span>
<input class="w-full bg-surface-container-low border border-outline-variant rounded-lg py-2.5 pl-10 pr-4 text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('email') border-error @enderror" id="email" name="email" placeholder="you@company.com" required type="email" value="{{ old('email') }}" autocomplete="username"/>
</div>
</div>
<div class="space-y-1.5">
<div class="flex justify-between items-center">
<label class="block text-sm font-medium text-on-surface-variant" for="password">Password</label>
<a class="text-xs font-semibold text-primary-container hover:underline transition-colors" href="#">Forgot Password?</a>
</div>
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">lock</span>
<input class="w-full bg-surface-container-low border border-outline-variant rounded-lg py-2.5 pl-10 pr-4 text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('password') border-error @enderror" id="password" name="password" placeholder="••••••••" required type="password" autocomplete="current-password"/>
</div>
</div>
<div class="flex items-center">
<input class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary/20" id="remember-me" name="remember" type="checkbox" value="1"/>
<label class="ml-2 block text-sm text-on-surface-variant" for="remember-me">Keep me logged in</label>
</div>
<button class="w-full bg-primary-container text-white font-semibold py-3 px-4 rounded-lg hover:bg-primary transition-all duration-200 active:scale-[0.98] focus:ring-4 focus:ring-primary/10 shadow-md flex items-center justify-center space-x-2" type="submit">
<span>Sign In</span>
<span class="material-symbols-outlined text-lg">arrow_forward</span>
</button>
</form>
</div>
</main>
@endsection
