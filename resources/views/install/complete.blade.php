@extends('install.layout')

@section('title', 'Done')
@section('heading', 'Installation complete')

@section('content')
    <p>The application is configured and <code>storage/app/install.lock</code> exists. Default admin credentials come from <code>AdminUserSeeder</code> (also saved in your <code>.env</code> as <code>ADMIN_*</code>).</p>

    @if (! empty($sessionStale))
        <div class="alert alert-info rounded-3 border-0 mb-3">
            We couldn’t verify the redirect token (bookmark or refreshed page). If you just finished install, the admin below is still correct unless you changed <code>.env</code>.
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <h6 class="card-subtitle text-muted text-uppercase small mb-2">Administrator login</h6>
            <dl class="row small mb-0">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9 font-monospace">{{ $adminName }}</dd>
                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9 font-monospace">{{ $adminEmail }}</dd>
                <dt class="col-sm-3">Password</dt>
                <dd class="col-sm-9 font-monospace">{{ $adminPassword }}</dd>
            </dl>
            <p class="text-danger small mt-3 mb-0">Change this password immediately after first sign-in (and update <code>ADMIN_PASSWORD</code> in <code>.env</code> if you re-seed).</p>
        </div>
    </div>

    <a href="{{ url('/admin/login') }}" class="btn btn-primary me-2">Admin login</a>
    <a href="{{ url('/') }}" class="btn btn-outline-secondary">Open site</a>
@endsection
