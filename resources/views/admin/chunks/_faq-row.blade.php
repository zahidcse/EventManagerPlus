<div class="faq-row rounded-xl border border-outline-variant bg-surface-container-lowest/40 transition-shadow hover:shadow-sm">
<div class="flex items-center justify-between gap-3 px-4 py-2.5 bg-white/80 border-b border-outline-variant/70">
<div class="flex items-center gap-2.5 min-w-0">
<span class="faq-row-badge flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary text-xs font-bold tabular-nums">1</span>
<p class="text-label-md font-semibold text-on-surface truncate">FAQ item</p>
</div>
<button type="button" class="faq-remove-btn inline-flex items-center justify-center rounded-lg p-1.5 text-on-surface-variant hover:bg-error-container/20 hover:text-error transition-colors {{ $showRemove ? '' : 'hidden' }}" aria-label="Remove FAQ" {{ $showRemove ? '' : 'disabled' }}>
<span class="material-symbols-outlined text-[20px]">delete</span>
</button>
</div>
<div class="p-4 space-y-3">
<div>
<label class="block text-xs font-semibold text-on-surface mb-1">Question</label>
<input name="faqs[{{ $idx }}][question]" class="faq-field-question w-full border border-outline-variant rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary/20 outline-none" type="text" value="{{ $faq['question'] ?? '' }}" placeholder="e.g. What time does registration open?"/>
</div>
<div>
<label class="block text-xs font-semibold text-on-surface mb-1">Answer</label>
<div class="faq-answer-editor">
<textarea id="faq-answer-{{ $idx }}" name="faqs[{{ $idx }}][answer]" data-admin-tinymce data-rich-ui-mode="split" data-rich-height="220" class="faq-field-answer w-full min-h-[180px] border border-outline-variant rounded-lg p-2 text-sm focus:ring-2 focus:ring-primary/20 outline-none" rows="6" placeholder="Your answer for attendees…">{!! $faq['answer'] ?? '' !!}</textarea>
</div>
</div>
</div>
</div>
