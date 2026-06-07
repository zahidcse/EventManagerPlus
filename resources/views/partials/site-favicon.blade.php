@php
  $faviconHref = $siteFaviconUrl ?? null;
  if ($faviconHref === null && ! empty($siteLogoUrl)) {
    $faviconHref = $siteLogoUrl;
  }
@endphp
@if(filled($faviconHref))
<link rel="icon" href="{{ $faviconHref }}" sizes="any"/>
@endif
