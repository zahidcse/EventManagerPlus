<footer>
  <div class="container">
    <div class="foot-grid">
      <div class="foot-about">
        <div class="brand {{ filled($siteLogoUrl ?? null) ? 'brand-has-logo' : '' }}">
          @if(filled($siteLogoUrl ?? null))
          <span class="brand-logo"><img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" decoding="async" /></span>
          @else
          <span class="brand-mark">
            <i data-lucide="ticket" class="icon" style="color:#fff"></i>
          </span>
          {{ $siteName }}
          @endif
        </div>
        <p>Discover and book tickets for memorable live experiences.</p>
        @include('public.partials.footer-social-links')
      </div>
      <div class="foot-col"><h4>Discover</h4><ul><li><a href="{{ route('events.index') }}">Events</a></li><li><a href="{{ url('/#how') }}">How it works</a></li><li><a href="{{ url('/#faq') }}">FAQ</a></li><li><a href="{{ url('/#contact') }}">Contact</a></li></ul></div>
      <div class="foot-col"><h4>Information</h4><ul>
        <li><a href="{{ route('blog.index') }}">Blog</a></li>
        @foreach($informationFooterPages as $footerPage)
          <li><a href="{{ route('pages.show', $footerPage) }}">{{ $footerPage->title }}</a></li>
        @endforeach
      </ul></div>
      <div class="foot-col"><h4>Support</h4><ul><li><a href="{{ url('/#contact') }}">Contact</a></li><li><a href="{{ url('/#faq') }}">Help</a></li></ul></div>
    </div>
    <div class="foot-bot">@include('public.partials.footer-copyright')</div>
  </div>
</footer>
