<section class="block layout-about {{ $variantClass ?? '' }}">
  <h2>About this event</h2>
  <div class="body-text">{!! $event->descriptionHtml() ?: '<p>No description yet.</p>' !!}</div>
</section>
