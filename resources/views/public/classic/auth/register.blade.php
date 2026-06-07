@extends('public.layouts.classic')

@section('title')
  Create account — {{ $siteName }}
@endsection

@section('meta_description')
  Create a free {{ $siteName }} account to book events faster.
@endsection

@section('content')
  <div class="container ep-auth-page">
    <div class="ep-auth-card contact-form">
      <p class="eyebrow">Account</p>
      <h1 style="font-size:26px;font-weight:800;margin:0 0 8px;letter-spacing:-.02em">Join {{ $siteName }}</h1>
      <p style="color:var(--muted);font-size:14px;margin-bottom:20px">Save your details and checkout faster next time.</p>

      @if ($errors->any())
        <div class="alert-error" role="alert">
          <ul style="margin:0;padding-left:18px">
            @foreach ($errors->all() as $msg)
              <li>{{ $msg }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="post" action="{{ route('register.submit') }}" class="auth-form-fields">
        @csrf
        @if(!empty($authRedirectPath))
          <input type="hidden" name="redirect" value="{{ $authRedirectPath }}" />
        @endif
        <label style="display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:var(--muted)">
          Name
          <input type="text" name="name" value="{{ old('name') }}" required autocomplete="name" />
        </label>
        <label style="display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:var(--muted)">
          Email
          <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
        </label>
        <label style="display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:var(--muted)">
          Password
          <input type="password" name="password" required autocomplete="new-password" />
        </label>
        <label style="display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:var(--muted)">
          Confirm password
          <input type="password" name="password_confirmation" required autocomplete="new-password" />
        </label>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:4px">Create
          account</button>
      </form>
      <p style="margin:16px 0 0;font-size:14px;color:var(--muted);text-align:center">
        Already registered? <a href="{{ route('login', array_filter(['redirect' => $authRedirectPath ?? null])) }}"
          style="color:var(--primary-2);font-weight:600">Sign in</a>
      </p>
    </div>
  </div>
@endsection