@if($galleryUrls->count() > 1)
<section class="block ep-event-gallery" aria-label="Event gallery">
  <h2 class="ep-event-gallery-title">Gallery</h2>
  <div class="gallery ep-gallery-grid">
    @foreach($galleryUrls as $i => $url)
      <button
        type="button"
        class="ep-gallery-thumb {{ $i === 0 ? 'active' : '' }}"
        data-gallery-index="{{ $i }}"
        data-src="{{ $url }}"
        aria-label="View image {{ $i + 1 }} of {{ $galleryUrls->count() }}"
      ><img src="{{ $url }}" alt="" /></button>
    @endforeach
  </div>
</section>

<div
  id="ep-gallery-lightbox"
  class="ep-gallery-lightbox"
  hidden
  role="dialog"
  aria-modal="true"
  aria-label="Event gallery viewer"
>
  <button type="button" class="ep-gallery-lightbox-backdrop" data-gallery-close aria-label="Close gallery"></button>
  <div class="ep-gallery-lightbox-panel">
    <button type="button" class="ep-gallery-lightbox-close" data-gallery-close aria-label="Close">&times;</button>
    <button type="button" class="ep-gallery-lightbox-nav ep-gallery-lightbox-nav--prev" data-gallery-prev aria-label="Previous image">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <div class="ep-gallery-lightbox-stage">
      <img id="ep-gallery-lightbox-img" src="" alt="" />
    </div>
    <button type="button" class="ep-gallery-lightbox-nav ep-gallery-lightbox-nav--next" data-gallery-next aria-label="Next image">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
    <div class="ep-gallery-lightbox-bottom">
      <div class="ep-gallery-lightbox-track" id="ep-gallery-lightbox-track" role="tablist" aria-label="Gallery thumbnails">
        @foreach($galleryUrls as $i => $url)
          <button
            type="button"
            class="ep-gallery-lightbox-thumb {{ $i === 0 ? 'is-active' : '' }}"
            data-gallery-slide="{{ $i }}"
            role="tab"
            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
            aria-label="Image {{ $i + 1 }}"
          ><img src="{{ $url }}" alt="" /></button>
        @endforeach
      </div>
    </div>
  </div>
</div>

@once
@push('scripts')
<script>
(function () {
  var urls = @json($galleryUrls->values());
  if (!urls.length) return;

  var lightbox = document.getElementById('ep-gallery-lightbox');
  var mainImg = document.getElementById('ep-gallery-lightbox-img');
  var track = document.getElementById('ep-gallery-lightbox-track');
  if (!lightbox || !mainImg) return;

  var current = 0;
  var slideDir = 0;

  function setHeroFromIndex(idx) {
    var heroImg = document.getElementById('heroImg');
    if (!heroImg || !urls[idx]) return;
    heroImg.src = urls[idx];
    document.querySelectorAll('.ep-gallery-grid .ep-gallery-thumb').forEach(function (btn, i) {
      btn.classList.toggle('active', i === idx);
    });
  }

  function scrollThumbIntoView(idx) {
    if (!track) return;
    var thumb = track.querySelector('[data-gallery-slide="' + idx + '"]');
    if (thumb && thumb.scrollIntoView) {
      thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
  }

  function updateThumbs(idx) {
    if (!track) return;
    track.querySelectorAll('[data-gallery-slide]').forEach(function (btn) {
      var i = parseInt(btn.getAttribute('data-gallery-slide'), 10);
      var on = i === idx;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    scrollThumbIntoView(idx);
  }

  function showSlide(idx, dir) {
    if (idx < 0) idx = urls.length - 1;
    if (idx >= urls.length) idx = 0;
    slideDir = dir || 0;
    current = idx;

    mainImg.classList.add('is-changing');
    mainImg.classList.remove('is-slide-left', 'is-slide-right');
    if (slideDir < 0) mainImg.classList.add('is-slide-left');
    if (slideDir > 0) mainImg.classList.add('is-slide-right');

    window.setTimeout(function () {
      mainImg.src = urls[current];
      mainImg.classList.remove('is-changing', 'is-slide-left', 'is-slide-right');
      updateThumbs(current);
      setHeroFromIndex(current);
    }, 120);
  }

  function openLightbox(idx) {
    current = typeof idx === 'number' ? idx : 0;
    mainImg.src = urls[current];
    mainImg.classList.remove('is-changing', 'is-slide-left', 'is-slide-right');
    updateThumbs(current);
    lightbox.hidden = false;
    document.body.classList.add('ep-gallery-lightbox-open');
    var closeBtn = lightbox.querySelector('.ep-gallery-lightbox-close');
    if (closeBtn) closeBtn.focus();
  }

  function closeLightbox() {
    lightbox.hidden = true;
    document.body.classList.remove('ep-gallery-lightbox-open');
  }

  document.querySelectorAll('.ep-gallery-grid .ep-gallery-thumb').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var idx = parseInt(btn.getAttribute('data-gallery-index'), 10);
      if (isNaN(idx)) return;
      openLightbox(idx);
    });
  });

  if (track) {
    track.querySelectorAll('[data-gallery-slide]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var idx = parseInt(btn.getAttribute('data-gallery-slide'), 10);
        if (isNaN(idx) || idx === current) return;
        showSlide(idx, idx > current ? 1 : -1);
      });
    });
  }

  lightbox.querySelectorAll('[data-gallery-close]').forEach(function (el) {
    el.addEventListener('click', closeLightbox);
  });

  var prevBtn = lightbox.querySelector('[data-gallery-prev]');
  var nextBtn = lightbox.querySelector('[data-gallery-next]');
  if (prevBtn) prevBtn.addEventListener('click', function () { showSlide(current - 1, -1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { showSlide(current + 1, 1); });

  document.addEventListener('keydown', function (e) {
    if (lightbox.hidden) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') showSlide(current - 1, -1);
    if (e.key === 'ArrowRight') showSlide(current + 1, 1);
  });
})();
</script>
@endpush
@endonce
@endif
