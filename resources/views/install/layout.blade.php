<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title') · Setup — {{ config('branding.project') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    @php
        $installStep = match (Route::currentRouteName()) {
            'install.setup' => 2,
            'install.complete' => 3,
            default => 1,
        };
        $s = $installStep;
    @endphp
    <style>
        :root {
            --inst-bg: #0f172a;
            --inst-bg-mid: #1e293b;
            --inst-accent: #818cf8;
            --inst-accent-soft: rgba(129, 140, 248, 0.25);
            --inst-glow: rgba(99, 102, 241, 0.45);
            --inst-success: #34d399;
            --inst-radius: 16px;
        }
        html { scroll-behavior: smooth; }
        body.install-host {
            min-height: 100vh;
            margin: 0;
            font-family: "DM Sans", system-ui, sans-serif;
            background: linear-gradient(165deg, #e8e9f3 0%, #f4f6fb 45%, #eef0f9 100%);
            color: #1e293b;
        }
        .install-shell {
            max-width: 52rem;
            margin: 0 auto;
            padding: 2rem 1.25rem 3rem;
        }
        /* Hero header */
        .install-hero {
            border-radius: var(--inst-radius);
            background: radial-gradient(120% 100% at 10% -20%, var(--inst-glow) 0%, transparent 55%),
                        linear-gradient(145deg, var(--inst-bg) 0%, var(--inst-bg-mid) 50%, #0c1222 100%);
            color: #f8fafc;
            padding: 2rem 1.75rem 1.5rem;
            box-shadow:
                0 4px 6px -1px rgb(15 23 42 / 0.15),
                0 0 0 1px rgb(255 255 255 / 0.06) inset;
            position: relative;
            overflow: hidden;
        }
        .install-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%236366f1' fill-opacity='0.07'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.9;
            pointer-events: none;
        }
        .install-hero-company {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgb(203 213 225 / 0.65);
            margin-bottom: 0.65rem;
        }
        .install-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--inst-accent);
            background: var(--inst-accent-soft);
            border: 1px solid rgb(129 140 248 / 0.35);
            border-radius: 999px;
            padding: 0.35rem 0.85rem;
            margin-bottom: 0.75rem;
        }
        .install-hero h1 {
            font-size: clamp(1.5rem, 4vw, 1.85rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            margin: 0 0 0.35rem;
            line-height: 1.2;
        }
        .install-hero p.lead-muted {
            margin: 0 0 1.75rem;
            font-size: 0.95rem;
            color: rgb(203 213 225 / 0.88);
            max-width: 28rem;
        }
        /* Step bar */
        .install-stepbar {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: 1fr auto 1fr auto 1fr;
            align-items: center;
            gap: 0;
        }
        @media (min-width: 480px) {
            .install-stepbar { gap: 0 0.25rem; }
        }
        .install-stepbar-track {
            height: 3px;
            border-radius: 3px;
            background: rgb(148 163 184 / 0.2);
            margin: 0 0.15rem;
        }
        .install-stepbar-track.filled {
            background: linear-gradient(90deg, var(--inst-success), var(--inst-accent));
        }
        .install-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            min-width: 0;
            padding: 0 0.25rem;
        }
        .install-step .bubble {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            border: 2px solid rgb(148 163 184 / 0.35);
            background: rgb(30 41 59 / 0.6);
            color: rgb(226 232 240 / 0.75);
            transition: transform 0.2s ease, border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .install-step.done .bubble {
            border-color: var(--inst-success);
            background: rgb(52 211 153 / 0.15);
            color: var(--inst-success);
        }
        .install-step.done .bubble svg { width: 1.15rem; height: 1.15rem; }
        .install-step.active .bubble {
            border-color: var(--inst-accent);
            background: linear-gradient(145deg, rgb(79 70 229 / 0.35), rgb(129 140 248 / 0.2));
            color: #fff;
            box-shadow: 0 0 0 4px rgb(129 140 248 / 0.2), 0 8px 24px rgb(79 70 229 / 0.25);
            transform: scale(1.06);
            animation: inst-pulse 2.5s ease-in-out infinite;
        }
        @keyframes inst-pulse {
            0%, 100% { box-shadow: 0 0 0 4px rgb(129 140 248 / 0.2), 0 8px 24px rgb(79 70 229 / 0.2); }
            50% { box-shadow: 0 0 0 8px rgb(129 140 248 / 0.12), 0 8px 28px rgb(79 70 229 / 0.3); }
        }
        .install-step.done-finale.active .bubble {
            border-color: var(--inst-success);
            background: linear-gradient(145deg, rgb(52 211 153 / 0.28), rgb(99 102 241 / 0.25));
            animation: inst-pulse-success 2.5s ease-in-out infinite;
        }
        @keyframes inst-pulse-success {
            0%, 100% { box-shadow: 0 0 0 4px rgb(52 211 153 / 0.15), 0 8px 24px rgb(16 185 129 / 0.2); }
            50% { box-shadow: 0 0 0 10px rgb(52 211 153 / 0.08), 0 8px 28px rgb(16 185 129 / 0.28); }
        }
        .install-step .caption {
            margin-top: 0.65rem;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: rgb(148 163 184 / 0.85);
            line-height: 1.25;
            max-width: 6.5rem;
        }
        @media (min-width: 400px) {
            .install-step .caption { font-size: 0.75rem; max-width: none; }
        }
        .install-step.active .caption { color: #e2e8f0; }
        .install-step.done-finale.active .caption { color: #a7f3d0; }
        .install-step.done:not(.active) .caption { color: rgb(52 211 153 / 0.85); }
        /* Main card */
        .install-panel {
            margin-top: -0.75rem;
            background: #fff;
            border-radius: var(--inst-radius);
            border: 1px solid rgb(226 232 240 / 0.9);
            box-shadow:
                0 24px 48px -12px rgb(15 23 42 / 0.1),
                0 12px 24px -10px rgb(15 23 42 / 0.06),
                0 0 0 1px rgb(255 255 255 / 0.9) inset;
            position: relative;
            z-index: 2;
        }
        .install-panel-heading {
            padding: 1.35rem 1.5rem 0;
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #0f172a;
            border-bottom: 1px solid rgb(241 245 249);
            margin-bottom: 0;
            padding-bottom: 1rem;
        }
        .install-body {
            padding: 1.25rem 1.5rem 1.5rem;
        }
        .install-body code { font-size: 0.855em; color: #64748b; background: #f8fafc; padding: 0.1rem 0.35rem; border-radius: 4px; }
        .check-ok { color: #059669; font-weight: 600; font-size: 0.875rem; }
        .check-fail { color: #dc2626; font-weight: 600; font-size: 0.875rem; }
        .install-panel .btn-primary {
            font-weight: 600;
            padding: 0.55rem 1.25rem;
            border-radius: 10px;
            background: linear-gradient(145deg, #4f46e5, #6366f1);
            border: none;
            box-shadow: 0 4px 14px rgb(79 70 229 / 0.35);
        }
        .install-panel .btn-primary:hover {
            background: linear-gradient(145deg, #4338ca, #4f46e5);
            transform: translateY(-1px);
        }
        .install-panel .table { --bs-table-border-color: #f1f5f9; }
    </style>
</head>
<body class="install-host">
<div class="install-shell">
        <header class="install-hero mb-4">
        <div class="install-hero-inner">
            <p class="install-hero-company mb-0">{{ config('branding.company') }}</p>
            <div class="install-hero-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Guided setup · {{ config('branding.project') }}
            </div>
            <h1>Install {{ config('branding.project') }}</h1>
            <p class="lead-muted">Configure your environment, database, and admin account — your site will be ready in a few steps.</p>

            <ol class="install-stepbar" aria-label="Installation progress">
                {{-- Step 1 — Requirements --}}
                <li class="install-step @if ($s > 1) done @elseif ($s === 1) active @endif" aria-current="{{ $s === 1 ? 'step' : 'false' }}">
                    <span class="bubble">
                        @if ($s > 1)
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                        @else
                            1
                        @endif
                    </span>
                    <span class="caption">Requirements</span>
                </li>
                <li class="install-stepbar-track @if ($s > 1) filled @endif" aria-hidden="true"></li>
                {{-- Step 2 — Configuration --}}
                <li class="install-step @if ($s > 2) done @elseif ($s === 2) active @endif" aria-current="{{ $s === 2 ? 'step' : 'false' }}">
                    <span class="bubble">
                        @if ($s > 2)
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                        @else
                            2
                        @endif
                    </span>
                    <span class="caption">Configuration</span>
                </li>
                <li class="install-stepbar-track @if ($s > 2) filled @endif" aria-hidden="true"></li>
                {{-- Step 3 — Finish --}}
                <li class="install-step @if ($s === 3) active done-finale @endif" aria-current="{{ $s === 3 ? 'step' : 'false' }}">
                    <span class="bubble">
                        @if ($s === 3)
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                        @else
                            3
                        @endif
                    </span>
                    <span class="caption">Launch</span>
                </li>
            </ol>
        </div>
    </header>

    <main class="install-panel">
        <h2 class="install-panel-heading">@yield('heading', 'Setup')</h2>
        <div class="install-body">
            @if ($errors->any())
                <div class="alert alert-danger rounded-3 border-0 mb-4" style="background: #fef2f2; color: #991b1b;">
                    <ul class="mb-0 small ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @if (session('installer_notice'))
                <div class="alert alert-info rounded-3 border-0 mb-4" style="background: #eff6ff; color: #1e40af;" role="status">
                    {{ session('installer_notice') }}
                </div>
            @endif
            @yield('content')
        </div>
    </main>
    <p class="text-center small mt-3 mb-0" style="color: #94a3b8;">{{ config('branding.company') }} · {{ config('branding.project') }}</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
