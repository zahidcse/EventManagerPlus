@php
    $espRows = old('event_speakers');
    if (! is_array($espRows)) {
        $espRows = $event->speakers->isNotEmpty()
            ? $event->speakers->map(fn ($s) => ['speaker_id' => (string) $s->id])->values()->all()
            : [['speaker_id' => '']];
    } else {
        $espRows = array_values($espRows);
        if (count($espRows) === 0) {
            $espRows = [['speaker_id' => '']];
        }
    }
    $speakerSearchOptions = [['value' => '', 'label' => '— Select speaker —']];
    foreach ($allSpeakers as $sp) {
        $speakerSearchOptions[] = [
            'value' => (string) $sp->id,
            'label' => $sp->name.($sp->headline ? ' — '.$sp->headline : ''),
        ];
    }
@endphp
<form method="post" action="{{ route('admin.events.update.speakers', $event) }}" id="event-speakers-form">
@csrf
@method('PUT')
<div class="p-8 max-w-5xl mx-auto pb-36">
@if(session('success'))
<div class="mb-6 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md flex items-center gap-2">
<span class="material-symbols-outlined text-primary">check_circle</span>
{{ session('success') }}
</div>
@endif
@include('admin.chunks._event-wizard-steps', ['currentStep' => 5, 'event' => $event])
<div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
<div>
<p class="text-label-md font-semibold text-on-surface-variant tracking-wide uppercase text-[11px] mb-1">Step 5 of 6</p>
<h1 class="text-2xl sm:text-[28px] font-bold text-on-surface tracking-tight">Speakers</h1>
<p class="text-body-md text-on-surface-variant mt-1 max-w-xl">{{ $event->title }}</p>
</div>
<a href="{{ route('admin.speakers.index') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-primary font-semibold text-body-sm hover:underline shrink-0">
<span class="material-symbols-outlined text-[18px]">open_in_new</span>
Manage speaker directory
</a>
</div>
@if($allSpeakers->isEmpty())
<div class="rounded-2xl border border-outline-variant bg-amber-50 text-amber-900 p-6 mb-6">
<p class="font-semibold mb-2">No speakers in the directory yet</p>
<p class="text-body-sm mb-4">Create speakers first, then assign them to this event.</p>
<a href="{{ route('admin.speakers.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white font-bold rounded-lg hover:opacity-90">Add a speaker</a>
</div>
@endif
<section class="rounded-2xl border border-outline-variant bg-white shadow-sm overflow-hidden mb-8">
<div class="px-6 py-5 border-b border-outline-variant bg-surface-container-low/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
<div>
<h2 class="text-lg font-bold text-on-surface">Event lineup</h2>
<p class="text-body-sm text-on-surface-variant mt-0.5">Add one row per speaker. Top to bottom is the order shown on the public site. Duplicate speakers in the list are ignored on save.</p>
</div>
<button type="button" id="event-speaker-add-row" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 border-primary/30 text-primary font-semibold text-label-md hover:bg-primary/5 transition-all active:scale-[0.98] shrink-0">
<span class="material-symbols-outlined text-[20px]">person_add</span>
Add slot
</button>
</div>
<div class="p-6">
<div id="event-speakers-rows" class="space-y-4">
@foreach($espRows as $idx => $row)
<div class="event-speaker-row flex flex-col sm:flex-row gap-3 sm:items-center sm:gap-4 p-4 rounded-xl border border-outline-variant bg-surface-container-low/20">
<div class="flex-1 min-w-0">
<label class="block text-[11px] font-bold text-outline uppercase mb-1">Speaker</label>
@include('partials.searchable-select', [
    'name' => 'event_speakers['.$idx.'][speaker_id]',
    'id' => 'event-speaker-select-'.$idx,
    'selected' => $row['speaker_id'] ?? '',
    'optionsKey' => 'event-speakers',
    'searchPlaceholder' => 'Search speaker…',
    'triggerPlaceholder' => '— Select speaker —',
    'inputClass' => $errors->has('event_speakers') || $errors->has('event_speakers.*') ? 'border-error' : '',
])
</div>
@if(count($espRows) > 1)
<button type="button" class="event-speaker-remove px-4 py-2 rounded-lg border border-error/40 text-error text-sm font-semibold hover:bg-error/5 shrink-0 self-start sm:self-center">Remove</button>
@endif
</div>
@endforeach
</div>
@error('event_speakers')<p class="text-error text-sm mt-3">{{ $message }}</p>@enderror
@error('event_speakers.*')<p class="text-error text-sm mt-3">{{ $message }}</p>@enderror
</div>
</section>
<p class="text-body-sm text-on-surface-variant">Tip: you can reuse the same speaker across many events. Photo and bio are edited in the speaker directory.</p>
</div>
<div class="fixed bottom-0 right-0 left-sidebar-width bg-white/95 backdrop-blur-sm border-t border-outline-variant px-6 sm:px-8 py-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 z-40">
<a href="{{ route('admin.events.edit.tickets', $event) }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-secondary font-semibold hover:bg-surface-container-low rounded-xl transition-colors order-2 sm:order-1">
<span class="material-symbols-outlined text-[20px]">arrow_back</span>
Previous step
</a>
<div class="flex items-center justify-end gap-3 order-1 sm:order-2">
@include('admin.chunks._event-wizard-save-button', [
    'stepTitle' => 'Speakers',
    'buttonClass' => 'px-5 py-2.5 text-on-surface font-semibold hover:bg-surface-container-low rounded-xl transition-colors border border-outline-variant',
])
<button type="submit" name="wizard_action" value="continue" class="px-6 py-2.5 bg-primary text-white font-bold rounded-xl hover:bg-primary-container transition-all shadow-md shadow-primary/25 active:scale-[0.98] inline-flex items-center gap-2">
Continue to content
<span class="material-symbols-outlined text-[18px]">arrow_forward</span>
</button>
</div>
</div>
</form>
<template id="event-speaker-row-template">
<div class="event-speaker-row flex flex-col sm:flex-row gap-3 sm:items-center sm:gap-4 p-4 rounded-xl border border-outline-variant bg-surface-container-low/20">
<div class="flex-1 min-w-0">
<label class="block text-[11px] font-bold text-outline uppercase mb-1">Speaker</label>
@include('partials.searchable-select', [
    'name' => 'event_speakers[__INDEX__][speaker_id]',
    'id' => 'event-speaker-select-__INDEX__',
    'selected' => '',
    'optionsKey' => 'event-speakers',
    'searchPlaceholder' => 'Search speaker…',
    'triggerPlaceholder' => '— Select speaker —',
])
</div>
<button type="button" class="event-speaker-remove px-4 py-2 rounded-lg border border-error/40 text-error text-sm font-semibold hover:bg-error/5 shrink-0 self-start sm:self-center">Remove</button>
</div>
</template>
@push('scripts')
<script>
  if (window.AdminSearchableSelect) {
    AdminSearchableSelect.registerOptions('event-speakers', @json($speakerSearchOptions));
  }
</script>
@endpush
<script>
(function () {
  var container = document.getElementById('event-speakers-rows');
  var template = document.getElementById('event-speaker-row-template');
  var addBtn = document.getElementById('event-speaker-add-row');
  if (!container || !template || !addBtn) return;

  function getNextIndex() {
    var max = -1;
    container.querySelectorAll('[data-searchable-value][name^="event_speakers["]').forEach(function (hidden) {
      var m = hidden.name.match(/^event_speakers\[(\d+)\]/);
      if (m) max = Math.max(max, parseInt(m[1], 10));
    });
    return max + 1;
  }

  function reindexSpeakerCombobox(row, idx) {
    var root = row.querySelector('[data-searchable-select]');
    var hidden = row.querySelector('[data-searchable-value]');
    var trigger = row.querySelector('.searchable-select-trigger');
    var search = row.querySelector('.searchable-select-search');
    var panel = row.querySelector('.searchable-select-panel');
    var list = row.querySelector('.searchable-select-options');
    var baseId = 'event-speaker-select-' + idx;
    if (hidden) hidden.name = 'event_speakers[' + idx + '][speaker_id]';
    if (root) {
      root.dataset.searchableId = baseId;
      delete root.dataset.ssReady;
    }
    if (hidden) hidden.id = baseId;
    if (trigger) {
      trigger.id = baseId + '-trigger';
      trigger.setAttribute('aria-controls', baseId + '-panel');
    }
    if (search) {
      search.id = baseId + '-search';
      search.setAttribute('aria-controls', baseId + '-listbox');
    }
    if (panel) panel.id = baseId + '-panel';
    if (list) list.id = baseId + '-listbox';
  }

  function bindRow(row) {
    var rm = row.querySelector('.event-speaker-remove');
    if (rm) rm.addEventListener('click', function () {
      if (container.querySelectorAll('.event-speaker-row').length <= 1) return;
      row.remove();
      toggleRemoveVisibility();
    });
    if (window.AdminSearchableSelect) {
      AdminSearchableSelect.initAll(row);
    }
  }

  function toggleRemoveVisibility() {
    var rows = container.querySelectorAll('.event-speaker-row');
    var mult = rows.length > 1;
    rows.forEach(function (row) {
      var rm = row.querySelector('.event-speaker-remove');
      if (!rm) {
        if (!mult) return;
        rm = document.createElement('button');
        rm.type = 'button';
        rm.className = 'event-speaker-remove px-4 py-2 rounded-lg border border-error/40 text-error text-sm font-semibold hover:bg-error/5 shrink-0 self-start sm:self-center';
        rm.textContent = 'Remove';
        rm.addEventListener('click', function () {
          if (container.querySelectorAll('.event-speaker-row').length <= 1) return;
          row.remove();
          toggleRemoveVisibility();
        });
        row.appendChild(rm);
      }
      rm.classList.toggle('hidden', !mult);
      rm.style.display = mult ? '' : 'none';
    });
  }

  container.querySelectorAll('.event-speaker-row').forEach(bindRow);
  toggleRemoveVisibility();

  addBtn.addEventListener('click', function () {
    var idx = getNextIndex();
    var frag = template.content.cloneNode(true);
    var row = frag.querySelector('.event-speaker-row');
    if (!row) return;
    reindexSpeakerCombobox(row, idx);
    container.appendChild(row);
    bindRow(row);
    toggleRemoveVisibility();
  });
})();
</script>
