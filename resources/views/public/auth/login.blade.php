@extends('public.layouts.classic')

@section('title')
Sign in — {{ $siteName }}
@endsection

@section('meta_description')
Sign in to your {{ $siteName }} account.
@endsection

@section('content')
<div class="container ep-auth-page">
  <div class="ep-auth-card contact-form">
    <p class="eyebrow">Account</p>
    <h1 style="font-size:26px;font-weight:800;margin:0 0 8px;letter-spacing:-.02em">Welcome back</h1>
    <p style="color:var(--muted);font-size:14px;margin-bottom:20px">Sign in to browse events and manage bookings.</p>

    @if ($errors->any())
      <div class="alert-error" role="alert">
        <ul style="margin:0;padding-left:18px">
          @foreach ($errors->all() as $msg)
            <li>{{ $msg }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="post" action="{{ route('login.submit') }}" class="auth-form-fields">
      @csrf
      @if(! empty($authRedirectPath))
      <input type="hidden" name="redirect" value="{{ $authRedirectPath }}" />
      @endif
      <label style="display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:var(--muted)">
        Email
        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
      </label>
      <label style="display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:var(--muted)">
        Password
        <input type="password" name="password" required autocomplete="current-password" />
      </label>
      <label style="display:flex;align-items:center;gap:10px;font-size:14px;color:var(--muted);cursor:pointer">
        <input type="checkbox" name="remember" value="1" @checked(old('remember')) /> Remember me
      </label>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">Sign in</button>
    </form>
    <p style="margin:16px 0 0;font-size:14px;color:var(--muted);text-align:center">
      Don’t have an account? <a href="{{ route('register', array_filter(['redirect' => $authRedirectPath ?? null])) }}" style="color:var(--primary-2);font-weight:600">Create one</a>
    </p>
    <p style="margin:12px 0 0;font-size:13px;color:var(--muted);text-align:center">
      Organizer or staff? <a href="{{ route('admin.login') }}" style="color:var(--muted);font-weight:600;text-decoration:underline;text-underline-offset:3px">Staff sign in</a>
    </p>
  </div>
</div>
@endsection
