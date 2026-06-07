@php
  $name = $name ?? 'select';
  $id = $id ?? str_replace(['[', ']'], ['-', ''], $name);
  $selected = (string) old($name, $selected ?? '');
  $options = $options ?? [];
  $optionsKey = $optionsKey ?? null;
  $searchPlaceholder = $searchPlaceholder ?? ($placeholder ?? 'Search…');
  $triggerPlaceholder = $triggerPlaceholder ?? 'Select…';
  $required = $required ?? false;
  $class = $class ?? 'w-full';
  $inputClass = $inputClass ?? '';
  $frontend = $frontend ?? false;

  $selectedLabel = '';
  foreach ($options as $opt) {
      if ((string) ($opt['value'] ?? '') === $selected) {
          $selectedLabel = (string) ($opt['label'] ?? '');
          break;
      }
  }
  $triggerText = $selectedLabel !== '' ? $selectedLabel : $triggerPlaceholder;
  $triggerMuted = $selectedLabel === '';
  $triggerClass = $frontend
    ? 'searchable-select-trigger searchable-select-trigger--ep'
    : 'searchable-select-trigger w-full flex items-center justify-between gap-2 pl-4 pr-3 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-body-md bg-white text-left '.$inputClass;
  $labelClass = $frontend
    ? 'searchable-select-trigger-label'.($triggerMuted ? ' is-placeholder' : '')
    : 'searchable-select-trigger-label truncate '.($triggerMuted ? 'text-on-surface-variant' : 'text-on-surface');
  $optionsJsonId = $id.'-options-json';
@endphp
@if(! $optionsKey)
  <script type="application/json" id="{{ $optionsJsonId }}">@json($options)</script>
@endif
<div
  class="searchable-select relative {{ $frontend ? 'searchable-select--frontend' : '' }} {{ $class }}"
  data-searchable-select
  data-searchable-id="{{ $id }}"
  @if($frontend) data-searchable-frontend="1" @endif
  @if($optionsKey) data-options-key="{{ $optionsKey }}" @endif
  @if(! $optionsKey) data-searchable-options-id="{{ $optionsJsonId }}" @endif
  data-trigger-placeholder="{{ $triggerPlaceholder }}"
>
  <button
    type="button"
    id="{{ $id }}-trigger"
    class="{{ $triggerClass }}"
    aria-haspopup="listbox"
    aria-expanded="false"
    aria-controls="{{ $id }}-panel"
  >
    <span class="{{ $labelClass }}">{{ $triggerText }}</span>
    @if($frontend)
      <span class="searchable-select-chevron" aria-hidden="true"></span>
    @else
      <span class="material-symbols-outlined text-on-surface-variant text-[22px] shrink-0" aria-hidden="true">expand_more</span>
    @endif
  </button>
  <input
    type="hidden"
    name="{{ $name }}"
    id="{{ $id }}"
    value="{{ $selected }}"
    data-searchable-value
    @if($required) required @endif
  />
  <div
    id="{{ $id }}-panel"
    class="searchable-select-panel{{ $frontend ? ' searchable-select-panel--ep' : '' }}"
    role="presentation"
    hidden
    aria-hidden="true"
  >
    <div class="searchable-select-search-wrap">
      @if($frontend)
        <svg class="searchable-select-search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      @else
        <span class="material-symbols-outlined searchable-select-search-icon" aria-hidden="true">search</span>
      @endif
      <input
        type="text"
        id="{{ $id }}-search"
        class="searchable-select-search {{ $frontend ? 'searchable-select-search--ep' : 'w-full pl-9 pr-3 py-2 text-body-md rounded-md border border-outline-variant focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white' }}"
        placeholder="{{ $searchPlaceholder }}"
        autocomplete="off"
        aria-controls="{{ $id }}-listbox"
      />
    </div>
    <ul
      id="{{ $id }}-listbox"
      class="searchable-select-options list-none m-0 p-0"
      role="listbox"
    ></ul>
  </div>
</div>

@once
  @push('styles')
    <style>
      .searchable-select-trigger[aria-expanded="true"] .material-symbols-outlined:last-child {
        transform: rotate(180deg);
      }
      .searchable-select-panel {
        display: none;
        visibility: hidden;
        pointer-events: none;
      }
      .searchable-select-panel.searchable-select-panel--open:not(.searchable-select-panel--ep) {
        display: flex;
        flex-direction: column;
        visibility: visible;
        pointer-events: auto;
        position: fixed;
        z-index: 200;
        margin: 0;
        box-sizing: border-box;
        background: #fff;
        border-radius: 0.5rem;
        border: 1px solid #c4c5d5;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12);
        overflow: hidden;
      }
      .searchable-select-panel--ep.searchable-select-panel--open {
        display: flex;
        flex-direction: column;
        visibility: visible;
        pointer-events: auto;
        position: fixed;
        z-index: 200;
        margin: 0;
        box-sizing: border-box;
        overflow: hidden;
      }
      .searchable-select-search-wrap {
        position: relative;
        flex-shrink: 0;
        padding: 0.5rem;
        border-bottom: 1px solid #e1e2e4;
        background: #f8f9fb;
      }
      .searchable-select-search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 18px;
        color: #757684;
        pointer-events: none;
      }
      .searchable-select-options {
        overflow-y: auto;
        max-height: 14rem;
      }
      .searchable-select-options .searchable-select-option {
        padding: 0.5rem 1rem;
        cursor: pointer;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #191c1e;
      }
      .searchable-select-options .searchable-select-option:hover,
      .searchable-select-options .searchable-select-option.is-active {
        background: #edeef0;
      }
      .searchable-select-options .searchable-select-group {
        padding: 0.35rem 1rem 0.15rem;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #757684;
        pointer-events: none;
      }
      .searchable-select-options .searchable-select-empty {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        color: #757684;
      }
    </style>
  @endpush
@endonce
