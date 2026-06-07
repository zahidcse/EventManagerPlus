@php
    /** @var \App\Models\User $user */
    $user = $user ?? auth()->user();
    $sizeClass = $sizeClass ?? 'w-8 h-8';
    $textClass = $textClass ?? 'text-xs';
    $parts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY);
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    if ($initials === '') {
        $initials = mb_strtoupper(mb_substr((string) ($user->email ?? '?'), 0, 1));
    }
@endphp
<div class="{{ $sizeClass }} rounded-full bg-primary-container text-white flex items-center justify-center {{ $textClass }} font-bold shrink-0 border border-white/20" aria-hidden="true">{{ $initials }}</div>
