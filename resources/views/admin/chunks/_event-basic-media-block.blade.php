@php
    $galleryCount = $event->galleryImages->count();
@endphp
<div
    id="event-hero-panel"
    class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm"
    data-hero-upload-url="{{ route('admin.events.hero.upload', $event) }}"
>
<div class="flex items-center gap-3 mb-4">
<div class="p-2 rounded-lg bg-primary/5 text-primary">
<span class="material-symbols-outlined">image</span>
</div>
<h2 class="text-headline-md font-bold text-on-surface">Hero Image</h2>
</div>
<div id="hero-ajax-status" class="hidden mb-3 rounded-lg px-3 py-2 text-body-sm" role="status"></div>
<label class="block cursor-pointer border-2 border-dashed border-outline-variant rounded-xl p-8 text-center hover:bg-primary/5 hover:border-primary transition-all">
<div class="w-12 h-12 bg-surface-container rounded-full flex items-center justify-center mx-auto mb-4">
<span class="material-symbols-outlined">upload_file</span>
</div>
<p class="text-body-md font-bold text-on-surface">Upload or replace hero image</p>
<input type="file" id="hero-ajax-input" class="sr-only" accept="image/jpeg,image/png,image/webp"/>
</label>
</div>
<div
    id="event-gallery-panel"
    class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm"
    data-upload-url="{{ route('admin.events.gallery.upload', $event) }}"
    data-gallery-count="{{ $galleryCount }}"
>
<div class="flex items-center gap-3 mb-4">
<div class="p-2 rounded-lg bg-secondary/10 text-secondary">
<span class="material-symbols-outlined">collections</span>
</div>
<div>
<h2 class="text-headline-md font-bold text-on-surface">Image gallery</h2>
</div>
</div>
<div id="gallery-ajax-status" class="hidden mb-3 rounded-lg px-3 py-2 text-body-sm" role="status"></div>
<div class="flex flex-wrap items-center gap-3 mb-4">
<input type="file" id="gallery-ajax-input" class="sr-only" accept="image/jpeg,image/png,image/webp"/>
<button type="button" id="gallery-ajax-pick" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-primary/35 text-primary font-semibold text-label-md hover:bg-primary/5 transition-all active:scale-[0.98]">
<span class="material-symbols-outlined text-[20px]">add_photo_alternate</span>
Upload image
</button>
</div>
<div id="gallery-grid" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
@foreach($event->galleryImages->sortBy('sort_order') as $img)
@include('admin.chunks._event-gallery-tile', ['img' => $img])
@endforeach
</div>
@if($galleryCount === 0)
<p id="gallery-empty-hint" class="text-body-sm text-on-surface-variant mt-2">No gallery images yet.</p>
@endif
</div>
@push('scripts')
<script>
(function () {
  var panel = document.getElementById('event-hero-panel');
  if (!panel) return;
  var uploadUrl = panel.getAttribute('data-hero-upload-url');
  var input = document.getElementById('hero-ajax-input');
  var statusEl = document.getElementById('hero-ajax-status');
  var token = document.querySelector('meta[name="csrf-token"]');
  var csrf = token ? token.getAttribute('content') : '';

  function setHeroStatus(type, msg) {
    if (!statusEl) return;
    statusEl.textContent = msg || '';
    statusEl.classList.remove('hidden', 'bg-error-container', 'text-error', 'bg-surface-container-low', 'text-on-surface');
    if (!msg) {
      statusEl.classList.add('hidden');
      return;
    }
    if (type === 'error') {
      statusEl.classList.add('bg-error-container', 'text-error');
    } else {
      statusEl.classList.add('bg-surface-container-low', 'text-on-surface');
    }
  }

  function applyHeroPreview(url) {
    var box = document.getElementById('event-hero-preview-box');
    if (!box || !url) return;
    var empty = document.getElementById('event-hero-preview-empty');
    var img = document.getElementById('event-hero-preview-img');
    if (empty) {
      empty.remove();
    }
    if (!img) {
      img = document.createElement('img');
      img.id = 'event-hero-preview-img';
      img.alt = '';
      img.className = 'w-full h-full object-cover';
      box.insertBefore(img, box.firstChild);
    }
    img.src = url + (url.indexOf('?') >= 0 ? '&' : '?') + 't=' + String(Date.now());
  }

  if (input) {
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) return;
      setHeroStatus('ok', 'Uploading…');
      var fd = new FormData();
      fd.append('image', file);
      fetch(uploadUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: fd,
        credentials: 'same-origin'
      }).then(function (r) {
        return r.text().then(function (text) {
          var j = {};
          if (text) {
            try {
              j = JSON.parse(text);
            } catch (e) {
              j = { message: text.slice(0, 200) };
            }
          }
          return { ok: r.ok, data: j };
        });
      }).then(function (res) {
        if (!res.ok) {
          var msg = (res.data && res.data.message) ? res.data.message : 'Upload failed.';
          if (res.data.errors && res.data.errors.image) {
            msg = Array.isArray(res.data.errors.image) ? res.data.errors.image[0] : res.data.errors.image;
          }
          throw new Error(msg);
        }
        if (!res.data.url) throw new Error('Invalid response');
        applyHeroPreview(res.data.url);
        setHeroStatus('ok', 'Hero image saved.');
        setTimeout(function () { setHeroStatus('ok', ''); }, 2000);
      }).catch(function (err) {
        setHeroStatus('error', err.message || 'Upload failed.');
      });
      input.value = '';
    });
  }
})();
</script>
@endpush
