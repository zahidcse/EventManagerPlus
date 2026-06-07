@extends('install.layout')

@section('title', 'Configuration')
@section('heading', 'Installation — site & database')

@section('content')
    <form method="post" action="{{ route('install.finish') }}">
        @csrf
        <h6 class="text-uppercase small text-muted mb-2">Site</h6>
        <div class="mb-3">
            <label class="form-label">Application name</label>
            <input type="text" name="app_name" class="form-control" value="{{ old('app_name', config('branding.project')) }}" required maxlength="191" />
        </div>
        <div class="mb-3">
            <label class="form-label">Site URL <span class="text-danger">*</span></label>
            <input type="url" name="app_url" class="form-control" value="{{ old('app_url', url('/')) }}" placeholder="https://your-domain.com" required />
        </div>

        <h6 class="text-uppercase small text-muted mb-2 mt-4">Administrator</h6>
        <p class="small text-muted">These credentials are written to your <code>.env</code> as <code>ADMIN_*</code> and create the initial admin account.</p>
        <div class="mb-3">
            <label class="form-label">Admin name</label>
            <input type="text" name="admin_name" class="form-control" value="{{ old('admin_name', $defaultAdminName) }}" required maxlength="191" autocomplete="name" />
        </div>
        <div class="mb-3">
            <label class="form-label">Admin email</label>
            <input type="email" name="admin_email" class="form-control" value="{{ old('admin_email', $defaultAdminEmail) }}" required maxlength="191" autocomplete="email" />
        </div>
        <div class="mb-3">
            <label class="form-label">Admin password</label>
            <input type="password" name="admin_password" class="form-control" required minlength="8" autocomplete="new-password" aria-describedby="adminPwHelp" />
            <div id="adminPwHelp" class="form-text">Minimum 8 characters.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirm admin password</label>
            <input type="password" name="admin_password_confirmation" class="form-control" required autocomplete="new-password" />
        </div>

        <h6 class="text-uppercase small text-muted mb-2 mt-4">Database (MySQL / MariaDB)</h6>
        <div class="mb-3">
            <label class="form-label">Driver</label>
            <select name="db_driver" class="form-select">
                <option value="mysql" @selected(old('db_driver', 'mysql') === 'mysql')>MySQL</option>
                <option value="mariadb" @selected(old('db_driver') === 'mariadb')>MariaDB</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Database name</label>
            <input type="text" name="db_database" class="form-control font-monospace small"
                   value="{{ old('db_database') }}" autocomplete="off" placeholder="your_database_name" />
        </div>

        <div class="row g-2">
            <div class="col-md-8 mb-2">
                <label class="form-label">Host</label>
                <input type="text" name="db_host" class="form-control" value="{{ old('db_host', '127.0.0.1') }}" />
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label">Port</label>
                <input type="text" name="db_port" class="form-control" value="{{ old('db_port', '3306') }}" />
            </div>
        </div>
        <p class="small text-muted">If you see SQLSTATE[HY000] [2002], start MySQL in Laragon, match the MySQL listening port shown in the Laragon tray menu, use host <strong>127.0.0.1</strong> rather than localhost, and confirm the database name exists (create it first in HeidiSQL/MySQL).</p>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="db_username" class="form-control" value="{{ old('db_username', 'root') }}" />
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="db_password" class="form-control" autocomplete="new-password" />
        </div>

        <p class="small text-muted mb-4">
            This step saves your database settings, runs <code>migrate</code>, then seeds the default admin.
            Login details appear on the next page.
        </p>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="fresh_database" id="fresh_database" value="1" @checked(old('fresh_database')) />
            <label class="form-check-label" for="fresh_database">
                <strong>Erase existing tables</strong> in this database first (<code>migrate:fresh</code>). Use this only when the DB already has leftovers from another install —
                <span class="text-danger">every table in this database will be dropped</span>.
            </label>
        </div>

        <p class="small text-muted mb-3">Migrating and seeding can take a minute or two. Leave this page open; refreshing may interrupt the install (especially on Windows).</p>

        <button type="submit" class="btn btn-primary">Run installation</button>
        <a href="{{ route('install.index') }}" class="btn btn-link">Back</a>
    </form>
@endsection
