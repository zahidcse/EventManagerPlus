@php
    $faqRows = old('faqs');
    if (! is_array($faqRows)) {
        $faqRows = $event->faqRows();
    }
    $faqRows = array_values($faqRows);
    if (count($faqRows) === 0) {
        $faqRows = [['question' => '', 'answer' => '']];
    }

    $timelineRows = old('timeline');
    if (! is_array($timelineRows)) {
        $timelineRows = $event->timelineRows();
    }
    $timelineRows = array_values($timelineRows);
    if (count($timelineRows) === 0) {
        $timelineRows = [['time_label' => '', 'title' => '']];
    }
@endphp
@include('admin.partials.rich-text-editor')
<form id="admin-event-content-form" method="post" action="{{ route('admin.events.update.content', $event) }}" enctype="multipart/form-data">
@csrf
@method('PUT')
<input type="hidden" name="wizard_panel" value="content"/>
<div class="p-8 max-w-6xl mx-auto pb-36">
@if(session('success'))
<div class="mb-6 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md flex items-center gap-2">
<span class="material-symbols-outlined text-primary">check_circle</span>
{{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="mb-6 rounded-xl border border-error/40 bg-error/5 px-4 py-3 text-body-md text-on-surface">
<p class="font-semibold text-error mb-2">Could not save — please check the fields below.</p>
<ul class="list-disc list-inside space-y-1 text-sm">
@foreach($errors->all() as $err)
<li>{{ $err }}</li>
@endforeach
</ul>
</div>
@endif
@include('admin.chunks._event-wizard-steps', ['currentStep' => 3, 'event' => $event])
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
<div class="lg:col-span-7 space-y-8">
@include('admin.chunks._event-basic-media-block', ['event' => $event])
<section class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm">
<div class="flex items-center gap-3 mb-6">
<span class="material-symbols-outlined text-primary bg-primary-fixed/30 p-2 rounded-lg">search_check</span>
<h2 class="text-headline-md font-semibold text-on-surface">SEO Settings</h2>
</div>
<div class="space-y-5">
<div>
<label class="block text-sm font-semibold text-on-surface mb-2">Meta Title</label>
<input name="meta_title" class="w-full border border-outline-variant rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Global Tech Summit 2024 | EventFlow" type="text" value="{{ old('meta_title', $event->meta_title) }}"/>
@error('meta_title')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
<div>
<label class="block text-sm font-semibold text-on-surface mb-2">Meta Description</label>
<textarea name="meta_description" class="w-full border border-outline-variant rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary/20 transition-all outline-none resize-none" placeholder="Join industry leaders..." rows="3">{{ old('meta_description', $event->meta_description) }}</textarea>
@error('meta_description')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
</div>
</div>
<div class="mt-8 p-4 bg-surface-container-low rounded-lg border border-outline-variant/30">
<span class="text-[10px] uppercase tracking-wider font-bold text-outline mb-2 block">Public URL (preview)</span>
<p class="text-[#006621] text-xs truncate">/events/{{ $event->slug }}</p>
</div>
</section>
<section class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm">
<div class="flex items-center gap-3 mb-6">
<span class="material-symbols-outlined text-primary bg-primary-fixed/30 p-2 rounded-lg shrink-0">timeline</span>
<div>
<h2 class="text-headline-md font-semibold text-on-surface">Event timeline</h2>
</div>
</div>
<ol id="timeline-rows-container" class="admin-timeline-track list-none m-0 p-0 rounded-2xl bg-primary-fixed/35 border border-outline-variant/60">
@foreach($timelineRows as $ti => $timelineItem)
@include('admin.chunks._timeline-row', ['idx' => $ti, 'item' => $timelineItem, 'showRemove' => count($timelineRows) > 1])
@endforeach
</ol>
<button type="button" id="timeline-add-row" class="admin-list-add-btn">
<span class="admin-list-add-btn__icon material-symbols-outlined" aria-hidden="true">add</span>
<span>Add new timeline</span>
</button>
@error('timeline')<p class="text-error text-sm mt-3">{{ $message }}</p>@enderror
<template id="timeline-row-template">
@include('admin.chunks._timeline-row', ['idx' => '__INDEX__', 'item' => ['time_label' => '', 'title' => ''], 'showRemove' => true])
</template>
</section>
</div>
<div class="lg:col-span-5 space-y-8 lg:sticky lg:top-24 self-start">
<div class="relative rounded-xl overflow-hidden border border-outline-variant aspect-video bg-surface-container shadow-sm" id="event-hero-preview-box">
@if($event->cover_image_path)
<img src="{{ asset('uploads/'.$event->cover_image_path) }}" alt="" class="w-full h-full object-cover" id="event-hero-preview-img"/>
@else
<div class="w-full h-full flex flex-col items-center justify-center text-on-surface-variant p-6 text-center" id="event-hero-preview-empty">
<span class="material-symbols-outlined text-[48px] mb-2 opacity-40">hide_image</span>
<p class="text-body-sm font-medium">No hero image yet</p>
<p class="text-label-md mt-1">Upload a hero image on the left.</p>
</div>
@endif
</div>
<section class="bg-white border border-outline-variant rounded-xl p-6 shadow-sm">
<div class="flex items-center gap-3 mb-6">
<span class="material-symbols-outlined text-primary bg-primary-fixed/30 p-2 rounded-lg shrink-0">quiz</span>
<div>
<h2 class="text-headline-md font-semibold text-on-surface">FAQ Builder</h2>
</div>
</div>
<div id="faq-rows-container" class="space-y-4">
@foreach($faqRows as $fi => $faq)
@include('admin.chunks._faq-row', ['idx' => $fi, 'faq' => $faq, 'showRemove' => count($faqRows) > 1])
@endforeach
</div>
<button type="button" id="faq-add-row" class="admin-list-add-btn">
<span class="admin-list-add-btn__icon material-symbols-outlined" aria-hidden="true">add</span>
<span>Add new FAQ</span>
</button>
@error('faqs')<p class="text-error text-sm mt-3">{{ $message }}</p>@enderror
<template id="faq-row-template">
@include('admin.chunks._faq-row', ['idx' => '__INDEX__', 'faq' => ['question' => '', 'answer' => ''], 'showRemove' => true])
</template>
</section>
</div>
</div>
</div>
<footer class="fixed bottom-0 right-0 left-sidebar-width h-20 bg-white/95 backdrop-blur-sm border-t border-outline-variant z-40 px-6 sm:px-8 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
<a href="{{ route('admin.events.edit.tickets', $event) }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-secondary font-semibold hover:bg-surface-container-low rounded-xl transition-colors order-2 sm:order-1 shrink-0">
<span class="material-symbols-outlined text-[20px]">arrow_back</span>
Previous step
</a>
<div class="flex items-center justify-end gap-3 order-1 sm:order-2 flex-wrap">
@include('admin.chunks._event-wizard-seat-plan-link')
@include('admin.chunks._event-wizard-save-button', ['currentStep' => 3, 'formId' => 'admin-event-content-form'])
<button type="submit" form="admin-event-content-form" name="wizard_action" value="continue" class="px-6 py-2.5 bg-primary text-white font-bold rounded-xl hover:bg-primary-container transition-all shadow-md shadow-primary/25 active:scale-[0.98] inline-flex items-center gap-2">
Continue to Advanced
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
@push('scripts')
<script>
(function () {
  function run() {
    var container = document.getElementById('faq-rows-container');
    var template = document.getElementById('faq-row-template');
    var addBtn = document.getElementById('faq-add-row');
    if (!container || !template || !addBtn) return;

    function getNextIndex() {
      var max = -1;
      container.querySelectorAll('.faq-row').forEach(function (row) {
        var n = row.querySelector('[name^="faqs["]');
        if (!n || !n.name) return;
        var m = n.name.match(/^faqs\[(\d+)\]/);
        if (m) max = Math.max(max, parseInt(m[1], 10));
      });
      return max + 1;
    }

    function renumberBadges() {
      var rows = container.querySelectorAll('.faq-row');
      rows.forEach(function (row, i) {
        var badge = row.querySelector('.faq-row-badge');
        if (badge) badge.textContent = String(i + 1);
      });
    }

    function toggleRemoveButtons() {
      var rows = container.querySelectorAll('.faq-row');
      var mult = rows.length > 1;
      rows.forEach(function (row) {
        var btn = row.querySelector('.faq-remove-btn');
        if (!btn) return;
        btn.classList.toggle('hidden', !mult);
        btn.disabled = !mult;
      });
    }

    function bindRow(row) {
      var rm = row.querySelector('.faq-remove-btn');
      if (rm) rm.addEventListener('click', function () {
        if (container.querySelectorAll('.faq-row').length <= 1) return;
        var ta = row.querySelector('.faq-field-answer');
        if (ta && window.adminRichTextRemove) window.adminRichTextRemove(ta);
        row.remove();
        renumberBadges();
        toggleRemoveButtons();
      });
    }

    container.querySelectorAll('.faq-row').forEach(bindRow);

    container.querySelectorAll('.faq-field-answer').forEach(function (ta) {
      if (window.adminRichTextInit) window.adminRichTextInit(ta);
    });

    addBtn.addEventListener('click', function () {
      var idx = getNextIndex();
      var html = template.innerHTML.replace(/__INDEX__/g, String(idx));
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var row = wrap.firstElementChild;
      if (!row) return;
      container.appendChild(row);
      renumberBadges();
      toggleRemoveButtons();
      bindRow(row);
      var taNew = row.querySelector('.faq-field-answer');
      if (taNew && window.adminRichTextInit) window.adminRichTextInit(taNew);
      if (window.adminFocusNewRow) window.adminFocusNewRow(row, '.faq-field-question');
    });

    renumberBadges();
    toggleRemoveButtons();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
</script>
@endpush
@push('styles')
<style>
  .admin-timeline-track {
    position: relative;
    padding: 20px 20px 8px 0;
    display: grid;
    gap: 0;
  }
  .admin-timeline-track::before {
    content: "";
    position: absolute;
    left: 39px;
    top: 36px;
    bottom: 28px;
    width: 2px;
    background: #c4c5d5;
    border-radius: 2px;
    pointer-events: none;
  }
  .admin-timeline-track .timeline-row {
    display: grid;
    grid-template-columns: 72px minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    padding: 10px 0 14px;
    list-style: none;
    transition: opacity 0.15s ease;
  }
  .admin-timeline-track .timeline-row.is-dragging { opacity: 0.45; }
  .admin-timeline-track .timeline-row.is-drag-over .admin-timeline-card {
    box-shadow: 0 0 0 2px rgba(0, 40, 142, 0.28);
  }
  .admin-timeline-marker-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding-top: 14px;
    position: relative;
    z-index: 1;
  }
  .admin-timeline-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid rgba(0, 40, 142, 0.35);
    box-shadow: 0 0 0 3px #dde1ff;
  }
  .admin-timeline-track .timeline-row.is-first .admin-timeline-dot {
    background: #00288e;
    border-color: #00288e;
    box-shadow: 0 0 0 4px #dde1ff;
  }
  .admin-timeline-card { padding: 14px 16px 16px; }
  .timeline-preview-date.is-placeholder,
  .timeline-preview-title.is-placeholder { opacity: 0.55; font-style: italic; font-weight: 500; }
  html.dark .admin-timeline-track {
    background: rgba(184, 196, 255, 0.08);
    border-color: #49454f;
  }
  html.dark .admin-timeline-track::before { background: #49454f; }
  html.dark .admin-timeline-track .timeline-row.is-drag-over .admin-timeline-card {
    box-shadow: 0 0 0 2px rgba(184, 196, 255, 0.35);
  }
  html.dark .admin-timeline-dot {
    background: #2b2930;
    border-color: rgba(184, 196, 255, 0.45);
    box-shadow: 0 0 0 3px #1c1b1f;
  }
  html.dark .admin-timeline-track .timeline-row.is-first .admin-timeline-dot {
    background: #b8c4ff;
    border-color: #b8c4ff;
    box-shadow: 0 0 0 4px #1c1b1f;
  }
</style>
@endpush
@push('scripts')
<script>
(function () {
  function run() {
    var container = document.getElementById('timeline-rows-container');
    var template = document.getElementById('timeline-row-template');
    var addBtn = document.getElementById('timeline-add-row');
    if (!container || !template || !addBtn) return;

    var dragRow = null;

    function getNextIndex() {
      var max = -1;
      container.querySelectorAll('.timeline-row').forEach(function (row) {
        var n = row.querySelector('[name^="timeline["]');
        if (!n || !n.name) return;
        var m = n.name.match(/^timeline\[(\d+)\]/);
        if (m) max = Math.max(max, parseInt(m[1], 10));
      });
      return max + 1;
    }

    function syncPreview(row) {
      var timeIn = row.querySelector('.timeline-field-time');
      var titleIn = row.querySelector('.timeline-field-title');
      var dateEl = row.querySelector('.timeline-preview-date');
      var titleEl = row.querySelector('.timeline-preview-title');
      if (!dateEl || !titleEl) return;
      var timeVal = timeIn ? String(timeIn.value || '').trim() : '';
      var titleVal = titleIn ? String(titleIn.value || '').trim() : '';
      dateEl.textContent = timeVal || 'Date or time';
      titleEl.textContent = titleVal || 'Timeline title';
      dateEl.classList.toggle('is-placeholder', !timeVal);
      titleEl.classList.toggle('is-placeholder', !titleVal);
    }

    function reindexRows() {
      container.querySelectorAll('.timeline-row').forEach(function (row, i) {
        row.querySelectorAll('[name^="timeline["]').forEach(function (el) {
          el.name = el.name.replace(/^timeline\[\d+\]/, 'timeline[' + i + ']');
        });
      });
    }

    function refreshMarkerStates() {
      container.querySelectorAll('.timeline-row').forEach(function (row, i) {
        row.classList.toggle('is-first', i === 0);
      });
    }

    function toggleRemoveButtons() {
      var rows = container.querySelectorAll('.timeline-row');
      var mult = rows.length > 1;
      rows.forEach(function (row) {
        var btn = row.querySelector('.timeline-remove-btn');
        if (!btn) return;
        btn.classList.toggle('hidden', !mult);
        btn.disabled = !mult;
      });
    }

    function clearDragState() {
      dragRow = null;
      container.querySelectorAll('.timeline-row').forEach(function (r) {
        r.classList.remove('is-dragging', 'is-drag-over');
      });
    }

    function bindDrag(row) {
      var handle = row.querySelector('.timeline-drag-handle');
      if (!handle) return;

      handle.addEventListener('dragstart', function (e) {
        dragRow = row;
        row.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', 'timeline');
        if (e.dataTransfer.setDragImage) {
          var card = row.querySelector('.admin-timeline-card');
          if (card) e.dataTransfer.setDragImage(card, 24, 24);
        }
      });

      handle.addEventListener('dragend', function () {
        clearDragState();
        reindexRows();
        refreshMarkerStates();
      });
    }

    function bindRow(row) {
      bindDrag(row);
      syncPreview(row);

      row.querySelectorAll('.timeline-field-time, .timeline-field-title').forEach(function (input) {
        input.addEventListener('input', function () { syncPreview(row); });
      });

      var rm = row.querySelector('.timeline-remove-btn');
      if (rm) rm.addEventListener('click', function () {
        if (container.querySelectorAll('.timeline-row').length <= 1) return;
        row.remove();
        reindexRows();
        refreshMarkerStates();
        toggleRemoveButtons();
      });
    }

    container.addEventListener('dragover', function (e) {
      if (!dragRow) return;
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      var target = e.target.closest('.timeline-row');
      if (!target || target === dragRow) return;
      container.querySelectorAll('.timeline-row').forEach(function (r) {
        r.classList.toggle('is-drag-over', r === target);
      });
      var rect = target.getBoundingClientRect();
      var before = e.clientY < rect.top + rect.height / 2;
      if (before) {
        container.insertBefore(dragRow, target);
      } else {
        container.insertBefore(dragRow, target.nextSibling);
      }
    });

    container.addEventListener('drop', function (e) {
      e.preventDefault();
      clearDragState();
      reindexRows();
      refreshMarkerStates();
    });

    container.querySelectorAll('.timeline-row').forEach(bindRow);

    addBtn.addEventListener('click', function () {
      var idx = getNextIndex();
      var html = template.innerHTML.replace(/__INDEX__/g, String(idx));
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var row = wrap.firstElementChild;
      if (!row) return;
      container.appendChild(row);
      reindexRows();
      refreshMarkerStates();
      toggleRemoveButtons();
      bindRow(row);
      if (window.adminFocusNewRow) window.adminFocusNewRow(row, '.timeline-field-time');
    });

    refreshMarkerStates();
    toggleRemoveButtons();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
</script>
@endpush
