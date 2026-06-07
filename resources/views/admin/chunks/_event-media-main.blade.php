@php
    $e = $event;
    $galleryCount = $event->galleryImages->count();
@endphp
<form method="post" action="{{ route('admin.events.update.media', $event) }}" enctype="multipart/form-data" id="event-media-form">
@csrf
@method('PUT')
<div class="p-8 max-w-5xl mx-auto pb-32">
@if(session('success'))
<div class="mb-6 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md">{{ session('success') }}</div>
@endif
@include('admin.chunks._event-wizard-steps', ['currentStep' => 2, 'event' => $event])
<p class="text-label-md font-semibold text-on-surface-variant tracking-wide uppercase text-[11px] mb-6 text-center">Step 2 of 6 · Cover &amp; image gallery</p>
<div class="grid grid-cols-12 gap-6">
<div class="col-span-12 lg:col-span-7 space-y-6">
<div class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
<div class="flex items-center gap-3 mb-6">
<div class="p-2 rounded-lg bg-primary/5 text-primary">
<span class="material-symbols-outlined">image</span>
</div>
<h2 class="text-headline-md font-bold text-on-surface">Cover image</h2>
</div>
<label class="block cursor-pointer border-2 border-dashed border-outline-variant rounded-xl p-8 text-center hover:bg-primary/5 hover:border-primary transition-all">
<div class="w-12 h-12 bg-surface-container rounded-full flex items-center justify-center mx-auto mb-4">
<span class="material-symbols-outlined">upload_file</span>
</div>
<p class="text-body-md font-bold text-on-surface">Upload hero image</p>
<p class="text-label-md text-on-surface-variant mt-2">Recommended: 1920×1080 (JPG, PNG, WebP)</p>
<input type="file" name="cover_image" class="sr-only" accept="image/jpeg,image/png,image/webp"/>
</label>
@error('cover_image')
<p class="text-error text-sm mt-3">{{ $message }}</p>
@enderror
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
<p class="text-body-sm text-on-surface-variant">Add photos <strong class="font-semibold text-on-surface">one at a time</strong>. Each file uploads and saves immediately (no need to press “Save draft”).</p>
</div>
</div>
<div id="gallery-ajax-status" class="hidden mb-3 rounded-lg px-3 py-2 text-body-sm" role="status"></div>
<div class="flex flex-wrap items-center gap-3 mb-4">
<input type="file" id="gallery-ajax-input" class="sr-only" accept="image/jpeg,image/png,image/webp"/>
<button type="button" id="gallery-ajax-pick" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-primary/35 text-primary font-semibold text-label-md hover:bg-primary/5 transition-all active:scale-[0.98]">
<span class="material-symbols-outlined text-[20px]">add_photo_alternate</span>
Upload image
</button>
<p class="text-label-md text-on-surface-variant">Pick one image per click; repeat to grow the list.</p>
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
</div>
<div class="col-span-12 lg:col-span-5 space-y-6">
<div class="relative rounded-xl overflow-hidden border border-outline-variant aspect-video bg-surface-container shadow-sm">
@if($e->cover_image_path)
<img src="{{ asset('uploads/'.$e->cover_image_path) }}" alt="" class="w-full h-full object-cover"/>
@else
<div class="w-full h-full flex flex-col items-center justify-center text-on-surface-variant p-6 text-center">
<span class="material-symbols-outlined text-[48px] mb-2 opacity-40">hide_image</span>
<p class="text-body-sm font-medium">No cover yet</p>
<p class="text-label-md mt-1">Upload a hero image on the left.</p>
</div>
@endif
</div>
<div class="bg-white rounded-xl border border-outline-variant overflow-hidden shadow-sm">
<div class="h-20 bg-primary-container flex items-center justify-center">
<span class="material-symbols-outlined text-white text-[40px] opacity-20">preview</span>
</div>
<div class="p-4">
<p class="text-label-md font-bold text-primary uppercase mb-1">Summary</p>
<h3 class="text-body-lg font-bold text-on-surface">{{ $e->title }}</h3>
<p id="gallery-count-summary" class="text-body-sm text-on-surface-variant mt-2">{{ $galleryCount }} image{{ $galleryCount === 1 ? '' : 's' }} in gallery</p>
</div>
</div>
</div>
</div>
</div>
<footer class="fixed bottom-0 right-0 left-sidebar-width h-20 bg-white border-t border-outline-variant z-40 px-8 flex items-center justify-between shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
<a href="{{ route('admin.events.edit', $event) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-on-surface font-semibold rounded-lg border border-outline-variant hover:bg-surface-container-low transition-colors">
<span class="material-symbols-outlined text-[20px]">arrow_back</span>
Back to basics
</a>
<div class="flex items-center gap-4">
@include('admin.chunks._event-wizard-save-button', [
    'stepTitle' => 'Media',
    'buttonClass' => 'px-6 py-2.5 rounded-lg border border-outline-variant text-on-surface font-bold text-body-md hover:bg-surface-container-low transition-colors active:scale-95',
])
<button type="submit" name="wizard_action" value="continue" class="px-8 py-2.5 rounded-lg bg-primary text-white font-bold text-body-md flex items-center gap-2 hover:bg-primary-container shadow-lg shadow-primary/20 transition-all active:scale-95">
Next: Location
<span class="material-symbols-outlined text-[18px]">arrow_forward</span>
</button>
</div>
</footer>
</form>
@push('scripts')
<script>
(function () {
  var panel = document.getElementById('event-gallery-panel');
  if (!panel) return;
  var uploadUrl = panel.getAttribute('data-upload-url');
  var input = document.getElementById('gallery-ajax-input');
  var pickBtn = document.getElementById('gallery-ajax-pick');
  var grid = document.getElementById('gallery-grid');
  var statusEl = document.getElementById('gallery-ajax-status');
  var countEl = document.getElementById('gallery-count-summary');
  var emptyHint = document.getElementById('gallery-empty-hint');
  var token = document.querySelector('meta[name="csrf-token"]');
  var csrf = token ? token.getAttribute('content') : '';

  function setStatus(type, msg) {
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

  function galleryCount() {
    return grid ? grid.querySelectorAll('.gallery-tile').length : 0;
  }

  function updateCountDisplay() {
    var n = galleryCount();
    if (countEl) {
      countEl.textContent = n + ' image' + (n === 1 ? '' : 's') + ' in gallery';
    }
    if (emptyHint) {
      emptyHint.classList.toggle('hidden', n > 0);
    }
  }

  function destroyUrl(id) {
    return uploadUrl.replace(/\/gallery$/, '/gallery/' + id);
  }

  function bindRemove(tile) {
    var btn = tile.querySelector('.gallery-remove-btn');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var id = tile.getAttribute('data-gallery-id');
      if (!id) return;
      btn.disabled = true;
      fetch(destroyUrl(id), {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      }).then(function (r) {
        if (!r.ok) throw new Error('Remove failed');
        return r.json();
      }).then(function () {
        tile.remove();
        updateCountDisplay();
        setStatus('ok', 'Image removed.');
        setTimeout(function () { setStatus('ok', ''); }, 2500);
      }).catch(function () {
        setStatus('error', 'Could not remove image. Try again.');
        btn.disabled = false;
      });
    });
  }

  if (grid) {
    grid.querySelectorAll('.gallery-tile').forEach(bindRemove);
  }

  if (pickBtn && input) {
    pickBtn.addEventListener('click', function () {
      input.value = '';
      input.click();
    });
  }

  if (input) {
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) return;
      setStatus('ok', 'Uploading…');
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
        return r.json().then(function (j) {
          if (!r.ok) {
            var msg = (j && j.message) ? j.message : 'Upload failed.';
            if (j.errors && j.errors.image) {
              msg = Array.isArray(j.errors.image) ? j.errors.image[0] : j.errors.image;
            }
            throw new Error(msg);
          }
          return j;
        });
      }).then(function (data) {
        if (!grid || !data.id || !data.url) throw new Error('Invalid response');
        var wrap = document.createElement('div');
        wrap.innerHTML =
          '<div class="gallery-tile relative rounded-lg border border-outline-variant overflow-hidden bg-surface-container" data-gallery-id="' +
          String(data.id) + '">' +
          '<img src="' + data.url + '" alt="" class="w-full h-28 object-cover"/>' +
          '<div class="flex items-center justify-end px-2 py-1.5 bg-surface-container-low">' +
          '<button type="button" class="gallery-remove-btn text-[11px] font-semibold text-error hover:underline disabled:opacity-50">Remove</button>' +
          '</div></div>';
        var tile = wrap.firstElementChild;
        grid.appendChild(tile);
        bindRemove(tile);
        updateCountDisplay();
        setStatus('ok', 'Image uploaded.');
        setTimeout(function () { setStatus('ok', ''); }, 2000);
      }).catch(function (err) {
        setStatus('error', err.message || 'Upload failed.');
      });
      input.value = '';
    });
  }
})();
</script>
@endpush
