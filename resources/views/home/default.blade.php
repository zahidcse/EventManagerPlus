<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@include('partials.site-favicon')
<title>{{ $siteSetting->homeMetaTitle($siteName) }}</title>
<meta name="description" content="{{ $siteSetting->homeMetaDescription() }}" />
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,sans-serif;background:#f4f4f5;color:#18181b;line-height:1.6;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;text-align:center}
h1{font-size:clamp(1.75rem,4vw,2.5rem);font-weight:700;margin-bottom:12px}
p{color:#71717a;max-width:32rem;margin-bottom:24px}
a{color:#7c3aed;font-weight:600;text-decoration:none}
a:hover{text-decoration:underline}
.links{display:flex;gap:20px;flex-wrap:wrap;justify-content:center}
</style>
</head>
<body>
<h1>{{ $siteName }}</h1>
<p>Welcome. Manage events in the admin area or switch to the Classic public theme under Settings.</p>
<div class="links">
<a href="{{ url('/admin/login') }}">Admin login</a>
</div>
</body>
</html>
