<li class="timeline-row" data-timeline-row>
  <div class="admin-timeline-marker-col">
    <button type="button" class="timeline-drag-handle inline-flex items-center justify-center w-8 h-8 rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface-variant cursor-grab active:cursor-grabbing touch-none" draggable="true" aria-label="Drag to reorder" title="Drag to reorder">
      <span class="material-symbols-outlined text-[20px]">drag_indicator</span>
    </button>
    <span class="admin-timeline-dot shrink-0" aria-hidden="true"></span>
  </div>
  <div class="admin-timeline-card bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm">
    <div class="admin-timeline-card-head flex items-start justify-between gap-3 mb-3 pb-3 border-b border-outline-variant/70">
      <div class="admin-timeline-preview min-w-0">
        <p class="timeline-preview-date m-0 mb-1 text-[13px] font-medium text-on-surface-variant">{{ filled($item['time_label'] ?? '') ? $item['time_label'] : 'Date or time' }}</p>
        <p class="timeline-preview-title m-0 text-base font-bold text-on-surface leading-snug">{{ filled($item['title'] ?? '') ? $item['title'] : 'Timeline title' }}</p>
      </div>
      <button type="button" class="timeline-remove-btn inline-flex items-center justify-center rounded-lg p-1.5 text-on-surface-variant hover:bg-error-container/20 hover:text-error transition-colors {{ $showRemove ? '' : 'hidden' }}" aria-label="Remove timeline item" {{ $showRemove ? '' : 'disabled' }}>
        <span class="material-symbols-outlined text-[20px]">delete</span>
      </button>
    </div>
    <div class="admin-timeline-card-fields space-y-3">
      <div>
        <label class="block text-xs font-semibold text-on-surface mb-1">Date / time</label>
        <input name="timeline[{{ $idx }}][time_label]" class="timeline-field-time w-full border border-outline-variant rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary/20 outline-none" type="text" value="{{ $item['time_label'] ?? '' }}" placeholder="e.g. May 22, 2026"/>
      </div>
      <div>
        <label class="block text-xs font-semibold text-on-surface mb-1">Title</label>
        <input name="timeline[{{ $idx }}][title]" class="timeline-field-title w-full border border-outline-variant rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary/20 outline-none" type="text" value="{{ $item['title'] ?? '' }}" placeholder="e.g. Registration opens"/>
      </div>
    </div>
  </div>
</li>
