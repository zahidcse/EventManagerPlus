@extends($accountLayout)

@section('title')
  Profile — {{ $siteName }}
@endsection

@section('meta_description')
  Update your profile and sign-in credentials.
@endsection

@section('content')
  <div class="container account-shell">
    @include('public.partials.account-tabs')

    <div class="account-panel">
      <h1 class="account-heading">Profile</h1>
      <p class="account-intro">Update your display name and sign-in email, or choose a new password.</p>

      @if(session('status'))
        <div class="alert-success" role="status">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="alert-error" role="alert">
          <ul style="margin:0;padding-left:18px">
            @foreach ($errors->all() as $msg)
              <li>{{ $msg }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="post" action="{{ route('account.profile.update') }}" class="account-profile-form">
        @csrf
        @method('put')
        <label class="prof-label">
          Name
          <input type="text" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" />
        </label>
        <label class="prof-label">
          Email
          <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username" />
        </label>
        <div class="prof-field prof-field--timezone">
          <span class="prof-label-text">Timezone</span>
          @include('partials.timezone-select-searchable', [
            'name' => 'timezone',
            'selected' => old('timezone', $user->timezone ?? ''),
            'allowEmpty' => true,
            'frontend' => true,
          ])
          <span class="account-field-hint">Event times on the site are shown in this timezone when you are signed in.</span>
        </div>
        <p class="account-section-label">Change password <span
            style="opacity:.75;font-weight:400;font-size:13px">(optional)</span></p>
        <label class="prof-label">
          Current password
          <input type="password" name="current_password" autocomplete="current-password" />
        </label>
        <label class="prof-label">
          New password
          <input type="password" name="password" autocomplete="new-password" />
        </label>
        <label class="prof-label">
          Confirm new password
          <input type="password" name="password_confirmation" autocomplete="new-password" />
        </label>
        <button type="submit" class="account-save-btn">Save changes</button>
      </form>
    </div>
  </div>
@endsection