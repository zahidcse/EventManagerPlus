@php

    $embedded = $embedded ?? false;

    $espRows = old('event_speakers');

    if (! is_array($espRows)) {

        $hasEvent = isset($event) && $event instanceof \App\Models\Event;

        $espRows = ($hasEvent && $event->speakers->isNotEmpty())

            ? $event->speakers->map(fn ($s) => ['speaker_id' => (string) $s->id])->values()->all()

            : [['speaker_id' => '']];

    } else {

        $espRows = array_values($espRows);

        if (count($espRows) === 0) {

            $espRows = [['speaker_id' => '']];

        }

    }

    $speakerDirEmpty = $allSpeakers->isEmpty();

    $forceSpeakerUi = is_array(old('event_speakers'));

    $showSpeakerAssign = ! $speakerDirEmpty || $forceSpeakerUi;

    $speakerSearchOptions = [['value' => '', 'label' => '— Select —']];

    foreach ($allSpeakers as $sp) {

        $speakerSearchOptions[] = [

            'value' => (string) $sp->id,

            'label' => $sp->name.($sp->headline ? ' — '.$sp->headline : ''),

        ];

    }

@endphp

@if($embedded)

@include('admin.partials.event-field-card-start', ['cardTitle' => 'Speakers', 'cardAddNew' => 'speaker', 'cardShowAddNew' => ! $speakerDirEmpty])

@else

<div class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">

<div class="flex items-center justify-between gap-2 mb-4">

<h3 class="text-headline-md font-bold text-on-surface">Speakers</h3>

@if(! $speakerDirEmpty)

<button type="button" class="text-label-md font-semibold text-primary shrink-0 hover:underline" data-quick-open-modal="speaker">Add new</button>

@endif

</div>

@endif

@if($speakerDirEmpty && ! $forceSpeakerUi)

<div id="event-speakers-directory-empty" class="rounded-lg border border-outline-variant bg-amber-50 text-amber-950 dark:bg-amber-950/20 dark:text-amber-100 p-4 {{ $embedded ? '' : 'mb-2' }}">

<p class="text-body-sm font-medium mb-3">No speakers in the directory yet. Add one to assign them to this event.</p>

<button type="button" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white font-bold rounded-lg text-sm hover:opacity-90" data-quick-open-modal="speaker">Add speaker</button>

</div>

@endif

<div id="event-speakers-assign-ui" class="{{ $showSpeakerAssign ? '' : 'hidden' }}">

<div id="event-speakers-rows" class="space-y-3">

@foreach($espRows as $idx => $row)

<div class="event-speaker-row flex flex-wrap gap-2 items-end">

<div class="flex-1 min-w-0">

@include('partials.searchable-select', [

    'name' => 'event_speakers['.$idx.'][speaker_id]',

    'id' => 'event-speaker-select-'.$idx,

    'selected' => $row['speaker_id'] ?? '',

    'optionsKey' => 'event-speakers',

    'searchPlaceholder' => 'Search speaker…',
    'triggerPlaceholder' => '— Select —',

    'inputClass' => $errors->has('event_speakers') || $errors->has('event_speakers.*') ? 'border-error' : '',

])

</div>

@if(count($espRows) > 1)

<button type="button" class="event-speaker-remove px-4 py-2.5 rounded-lg border border-error/40 text-error text-sm font-semibold hover:bg-error/5 shrink-0">Remove</button>

@endif

</div>

@endforeach

</div>

<button type="button" id="event-speaker-add-row" class="mt-2 text-label-md font-semibold text-primary hover:underline">Add another speaker</button>

@error('event_speakers')<p class="text-error text-sm mt-3">{{ $message }}</p>@enderror

@error('event_speakers.*')<p class="text-error text-sm mt-3">{{ $message }}</p>@enderror

<template id="event-speaker-row-template">

<div class="event-speaker-row flex flex-wrap gap-2 items-end">

<div class="flex-1 min-w-0">

@include('partials.searchable-select', [

    'name' => 'event_speakers[__INDEX__][speaker_id]',

    'id' => 'event-speaker-select-__INDEX__',

    'selected' => '',

    'optionsKey' => 'event-speakers',

    'searchPlaceholder' => 'Search speaker…',
    'triggerPlaceholder' => '— Select —',

])

</div>

<button type="button" class="event-speaker-remove px-4 py-2.5 rounded-lg border border-error/40 text-error text-sm font-semibold hover:bg-error/5 shrink-0">Remove</button>

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

        rm.className = 'event-speaker-remove px-4 py-2.5 rounded-lg border border-error/40 text-error text-sm font-semibold hover:bg-error/5 shrink-0';

        rm.textContent = 'Remove';

        rm.addEventListener('click', function () {

          if (container.querySelectorAll('.event-speaker-row').length <= 1) return;

          row.remove();

          toggleRemoveVisibility();

        });

        row.appendChild(rm);

      }

      rm.classList.toggle('hidden', !mult);

    });

  }



  container.querySelectorAll('.event-speaker-row').forEach(bindRow);

  toggleRemoveVisibility();



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

</div>


@if($embedded)

@include('admin.partials.event-field-card-end')

@else

</div>

@endif

