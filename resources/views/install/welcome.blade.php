@extends('install.layout')

@section('title', 'Welcome')
@section('heading', 'Installation — requirements')

@section('content')
    <p>This wizard creates <code>.env</code> if needed, checks requirements, then runs <code>migrate</code> and <code>db:seed</code> (default admin from the seeder). Tick <strong>Erase existing tables</strong> on the next step if the database has leftover tables from an old install. File-based sessions/caches are used during install.</p>
    <p class="small text-muted mb-3">If URLs like <code>/</code> or <code>/admin/login</code> return <strong>404</strong>: set your site document root to the <code>public</code> directory, or use the project root <code>.htaccess</code> that forwards into <code>public/</code>.</p>
    <table class="table table-sm align-middle">
        <tbody>
            @foreach ($checks as $c)
                <tr>
                    <td class="fw-medium">{{ $c['label'] }}</td>
                    <td class="text-end">
                        @if ($c['pass'])
                            <span class="check-ok">OK</span>
                        @else
                            <span class="check-fail">Failed</span>
                        @endif
                    </td>
                </tr>
                @if (! $c['pass'] && $c['detail'])
                    <tr><td colspan="2" class="text-muted small pt-0">{{ $c['detail'] }}</td></tr>
                @endif
            @endforeach
        </tbody>
    </table>

    @php $allPass = collect($checks)->every(fn ($c) => $c['pass']); @endphp

    @if ($allPass)
        <a href="{{ route('install.setup') }}" class="btn btn-primary">Continue</a>
    @else
        <button type="button" class="btn btn-secondary" disabled>Fix the items above, then refresh</button>
    @endif
@endsection
