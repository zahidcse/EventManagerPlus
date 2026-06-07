@php
    $isEdit = isset($event) && $event instanceof \App\Models\Event;
    $e = $isEdit ? $event : null;
    $eventTimezone = old('timezone', $e?->eventTimezone() ?? 'UTC');
    $startWall = $e ? $e->adminWallClockFor($e->starts_at) : ['date' => '', 'time' => ''];
    $endWall = $e ? $e->adminWallClockFor($e->ends_at) : ['date' => '', 'time' => ''];
    $startDate = old('start_date', $startWall['date']);
    $startTime = old('start_time', $startWall['time']);
    $endDate = old('end_date', $endWall['date']);
    $endTime = old('end_time', $endWall['time']);
    $scheduleType = old('schedule_type', $e?->schedule_type ?? 'single');
    if (! in_array($scheduleType, ['single', 'recurring', 'custom_interval'], true)) {
        $scheduleType = 'single';
    }
    $recurrenceEnds = old('recurrence_ends_on', $e?->recurrence_ends_on?->format('Y-m-d'));
    $recurrenceWeekdaysOld = old('recurrence_weekdays', $e?->recurrence_weekdays ?? []);
    if (! is_array($recurrenceWeekdaysOld)) {
        $recurrenceWeekdaysOld = [];
    }
    $recurrenceWeekdaysOld = array_map('intval', $recurrenceWeekdaysOld);
    $customScheduleDates = old('custom_schedule_dates', $e?->custom_schedule_dates ?? []);
    if (! is_array($customScheduleDates)) {
        $customScheduleDates = [];
    }
@endphp
<form method="post" action="{{ $isEdit ? route('admin.events.update', $e) : route('admin.events.store') }}" id="event-basic-form">
@csrf
@if($isEdit)
@method('PUT')
@endif
<div class="p-8 max-w-6xl mx-auto pb-32">
@if(session('success'))
<div class="mb-6 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="mb-6 rounded-xl border border-error/40 bg-error/5 px-4 py-3 text-body-md text-on-surface">
<p class="font-semibold text-error mb-2">Something prevented saving — please check the fields below.</p>
<ul class="list-disc list-inside space-y-1 text-sm text-on-surface">
@foreach($errors->all() as $err)
<li>{{ $err }}</li>
@endforeach
</ul>
</div>
@endif
@include('admin.chunks._event-wizard-steps', ['currentStep' => 1, 'event' => $e])
<div class="grid grid-cols-12 gap-6">
<div class="col-span-12 space-y-6">
<div class="bg-white rounded-xl border border-outline-variant p-6 shadow-sm">
<div class="flex items-center gap-3 mb-6">
<div class="p-2 rounded-lg bg-primary/5 text-primary">
<span class="material-symbols-outlined">edit_note</span>
</div>
<h2 class="text-headline-md font-bold text-on-surface">Basic Information</h2>
</div>
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
<div class="space-y-5 min-w-0 lg:col-span-8">
<div>
<label class="block text-label-md font-bold text-on-surface-variant mb-2 uppercase tracking-wider">Event Title</label>
<input name="title" class="w-full px-4 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-body-lg @error('title') border-error @enderror" placeholder="e.g. Annual Tech Summit 2024" type="text" value="{{ old('title', $e?->title) }}" required/>
@error('title')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<div>
<label class="block text-label-md font-bold text-on-surface-variant mb-2 uppercase tracking-wider">Visibility</label>
<div class="flex flex-wrap items-center gap-6 min-h-[50px]">
<label class="flex items-center gap-2 cursor-pointer">
<input class="text-primary focus:ring-primary" name="visibility" type="radio" value="public" @checked(old('visibility', $e?->visibility ?? 'public') === 'public')/>
<span class="text-body-md">Public</span>
</label>
<label class="flex items-center gap-2 cursor-pointer">
<input class="text-primary focus:ring-primary" name="visibility" type="radio" value="private" @checked(old('visibility', $e?->visibility ?? 'public') === 'private')/>
<span class="text-body-md">Private</span>
</label>
</div>
@error('visibility')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<div>
<label class="block text-label-md font-bold text-on-surface-variant mb-2 uppercase tracking-wider">Schedule type</label>
<div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-6 min-h-[44px]">
<label class="flex items-center gap-2 cursor-pointer">
<input class="text-primary focus:ring-primary event-schedule-type-input" name="schedule_type" type="radio" value="single" @checked($scheduleType === 'single')/>
<span class="text-body-md">Single event</span>
</label>
@if($editionIsFree ?? false)
<div class="relative w-full rounded-xl border border-outline-variant/80 bg-surface-container-low/30 p-4 space-y-4">
<div class="absolute top-3 right-3 z-10">@include('admin.chunks._premium-badge')</div>
<div class="opacity-60 pointer-events-none select-none space-y-4 pt-6">
<div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-6">
<label class="flex items-center gap-2 cursor-not-allowed">
<input class="text-primary focus:ring-primary" type="radio" value="recurring" disabled tabindex="-1"/>
<span class="text-body-md">Recurring (weekly)</span>
</label>
<label class="flex items-center gap-2 cursor-not-allowed">
<input class="text-primary focus:ring-primary" type="radio" value="custom_interval" disabled tabindex="-1"/>
<span class="text-body-md">Custom dates</span>
</label>
</div>
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Repeat on weekdays</label>
<div class="flex flex-wrap gap-2">
@foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $i => $dayLabel)
<label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-outline-variant bg-white">
<input type="checkbox" value="{{ $i }}" class="rounded border-outline-variant text-primary" disabled tabindex="-1" @checked(in_array($i, [1, 3, 5], true))/>
<span class="text-body-sm text-on-surface">{{ $dayLabel }}</span>
</label>
@endforeach
</div>
</div>
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Occurrence dates</label>
<div class="space-y-2 max-w-xl">
<div class="flex items-stretch gap-2">
<input type="date" value="" class="flex-1 min-w-0 px-4 py-3 rounded-lg border border-outline-variant text-body-md" disabled tabindex="-1"/>
</div>
</div>
</div>
</div>
</div>
@else
<label class="flex items-center gap-2 cursor-pointer">
<input class="text-primary focus:ring-primary event-schedule-type-input" name="schedule_type" type="radio" value="recurring" @checked($scheduleType === 'recurring')/>
<span class="text-body-md">Recurring (weekly)</span>
</label>
<label class="flex items-center gap-2 cursor-pointer">
<input class="text-primary focus:ring-primary event-schedule-type-input" name="schedule_type" type="radio" value="custom_interval" @checked($scheduleType === 'custom_interval')/>
<span class="text-body-md">Custom dates</span>
</label>
@endif
</div>
@error('schedule_type')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
@if(! ($editionIsFree ?? false))
<div data-schedule-for="recurring" class="space-y-2 {{ $scheduleType === 'recurring' ? '' : 'hidden' }}">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Repeat on weekdays</label>
<div class="flex flex-wrap gap-2">
@foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $i => $dayLabel)
<label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-outline-variant bg-white cursor-pointer hover:border-primary/40">
<input type="checkbox" name="recurrence_weekdays[]" value="{{ $i }}" class="rounded border-outline-variant text-primary focus:ring-primary" @checked(in_array($i, $recurrenceWeekdaysOld, true))/>
<span class="text-body-sm text-on-surface">{{ $dayLabel }}</span>
</label>
@endforeach
</div>
@error('recurrence_weekdays')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<div data-schedule-for="recurring" class="space-y-2 {{ $scheduleType === 'recurring' ? '' : 'hidden' }}">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Series ends on <span class="font-normal normal-case text-on-surface-variant">(optional)</span></label>
<input name="recurrence_ends_on" class="w-full max-w-md px-4 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 transition-all text-body-md @error('recurrence_ends_on') border-error @enderror" type="date" value="{{ $recurrenceEnds }}"/>
@error('recurrence_ends_on')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<div data-schedule-for="custom_interval" class="space-y-3 {{ $scheduleType === 'custom_interval' ? '' : 'hidden' }}">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Occurrence dates</label>
<div id="custom-schedule-dates-rows" class="space-y-2 max-w-xl">
@forelse($customScheduleDates as $cDate)
<div class="custom-schedule-date-row flex items-stretch gap-2">
<input type="date" name="custom_schedule_dates[]" value="{{ $cDate }}" class="flex-1 min-w-0 px-4 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 transition-all text-body-md"/>
<button type="button" class="custom-schedule-remove-date px-3 py-2 rounded-lg border border-error/40 text-error text-sm font-semibold hover:bg-error/5 shrink-0" title="Remove date">Remove</button>
</div>
@empty
<div class="custom-schedule-date-row flex items-stretch gap-2">
<input type="date" name="custom_schedule_dates[]" value="" class="flex-1 min-w-0 px-4 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 transition-all text-body-md"/>
<button type="button" class="custom-schedule-remove-date px-3 py-2 rounded-lg border border-error/40 text-error text-sm font-semibold hover:bg-error/5 shrink-0" title="Remove date">Remove</button>
</div>
@endforelse
</div>
<button type="button" id="custom-schedule-add-date" class="text-label-md font-semibold text-primary hover:underline">+ Add another date</button>
@error('custom_schedule_dates')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<template id="custom-schedule-date-row-template">
<div class="custom-schedule-date-row flex items-stretch gap-2">
<input type="date" name="custom_schedule_dates[]" value="" class="flex-1 min-w-0 px-4 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 transition-all text-body-md"/>
<button type="button" class="custom-schedule-remove-date px-3 py-2 rounded-lg border border-error/40 text-error text-sm font-semibold hover:bg-error/5 shrink-0" title="Remove date">Remove</button>
</div>
</template>
@endif
<div class="space-y-2">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Event timezone</label>
@include('partials.timezone-select-searchable', [
  'name' => 'timezone',
  'selected' => $eventTimezone,
  'required' => true,
  'class' => 'w-full max-w-xl',
])
@error('timezone')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div class="space-y-2">
<div data-schedule-for="single" class="{{ $scheduleType === 'single' ? '' : 'hidden' }}">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Start date &amp; time</label>
</div>
<div data-schedule-for="recurring" class="{{ $scheduleType === 'recurring' ? '' : 'hidden' }}">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">First session start</label>
</div>
<div data-schedule-for="custom_interval" class="{{ $scheduleType === 'custom_interval' ? '' : 'hidden' }}">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Session start time <span class="font-normal normal-case text-on-surface-variant">(each day above)</span></label>
</div>
<div class="flex gap-2 items-end">
<div data-schedule-for="single recurring" class="flex flex-1 min-w-0 {{ in_array($scheduleType, ['single', 'recurring'], true) ? '' : 'hidden' }}">
<input name="start_date" class="flex-1 min-w-0 px-4 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 transition-all text-body-md" type="date" value="{{ $startDate }}"/>
</div>
<input name="start_time" class="event-time-input shrink-0 pl-4 pr-10 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 transition-all text-body-md" type="time" value="{{ $startTime }}"/>
</div>
@error('start_date')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
@error('start_time')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
<div class="space-y-2">
<div data-schedule-for="single" class="{{ $scheduleType === 'single' ? '' : 'hidden' }}">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">End date &amp; time</label>
</div>
<div data-schedule-for="recurring" class="{{ $scheduleType === 'recurring' ? '' : 'hidden' }}">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">First session end</label>
</div>
<div data-schedule-for="custom_interval" class="{{ $scheduleType === 'custom_interval' ? '' : 'hidden' }}">
<label class="block text-label-md font-bold text-on-surface-variant uppercase tracking-wider">Session end time <span class="font-normal normal-case text-on-surface-variant">(each day above)</span></label>
</div>
<div class="flex gap-2 items-end">
<div data-schedule-for="single recurring" class="flex flex-1 min-w-0 {{ in_array($scheduleType, ['single', 'recurring'], true) ? '' : 'hidden' }}">
<input name="end_date" class="flex-1 min-w-0 px-4 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 transition-all text-body-md" type="date" value="{{ $endDate }}"/>
</div>
<input name="end_time" class="event-time-input shrink-0 pl-4 pr-10 py-3 rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 transition-all text-body-md" type="time" value="{{ $endTime }}"/>
</div>
@error('end_date')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
@error('end_time')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('event-basic-form');
    if (!form) return;

    function syncEventSchedulePanels() {
        var checked = form.querySelector('input[name="schedule_type"]:checked');
        var type = checked ? checked.value : 'single';
        form.querySelectorAll('[data-schedule-for]').forEach(function (el) {
            var raw = el.getAttribute('data-schedule-for');
            var forTypes = raw ? raw.trim().split(/\s+/).filter(Boolean) : [];
            el.classList.toggle('hidden', forTypes.indexOf(type) === -1);
        });
        var startDateInp = form.querySelector('input[name="start_date"]');
        var endDateInp = form.querySelector('input[name="end_date"]');
        if (startDateInp) {
            startDateInp.disabled = type === 'custom_interval';
        }
        if (endDateInp) {
            endDateInp.disabled = type === 'custom_interval';
        }
        form.querySelectorAll('input[name="recurrence_weekdays[]"]').forEach(function (cb) {
            cb.disabled = type !== 'recurring';
        });
        var recEndInp = form.querySelector('input[name="recurrence_ends_on"]');
        if (recEndInp) {
            recEndInp.disabled = type !== 'recurring';
        }
        form.querySelectorAll('input[name="custom_schedule_dates[]"]').forEach(function (inp) {
            inp.disabled = type !== 'custom_interval';
        });
    }

    form.querySelectorAll('input[name="schedule_type"]').forEach(function (r) {
        r.addEventListener('change', syncEventSchedulePanels);
    });

    var customRows = document.getElementById('custom-schedule-dates-rows');
    var customTpl = document.getElementById('custom-schedule-date-row-template');
    var customAdd = document.getElementById('custom-schedule-add-date');
    if (customRows && customTpl && customAdd) {
        function customRowCount() {
            return customRows.querySelectorAll('.custom-schedule-date-row').length;
        }
        function updateCustomRemoveButtons() {
            var mult = customRowCount() > 1;
            customRows.querySelectorAll('.custom-schedule-remove-date').forEach(function (b) {
                b.disabled = !mult;
                b.classList.toggle('opacity-40', !mult);
                b.classList.toggle('pointer-events-none', !mult);
            });
        }
        function bindCustomRemove(row) {
            var rm = row.querySelector('.custom-schedule-remove-date');
            if (!rm) return;
            rm.addEventListener('click', function () {
                if (customRowCount() <= 1) return;
                row.remove();
                updateCustomRemoveButtons();
            });
        }
        customRows.querySelectorAll('.custom-schedule-date-row').forEach(bindCustomRemove);
        updateCustomRemoveButtons();
        customAdd.addEventListener('click', function () {
            var frag = customTpl.content.cloneNode(true);
            var row = frag.querySelector('.custom-schedule-date-row');
            if (row) {
                customRows.appendChild(row);
                bindCustomRemove(row);
                updateCustomRemoveButtons();
            }
        });
    }

    syncEventSchedulePanels();
});
</script>
@endpush
<div>
<label class="block text-label-md font-bold text-on-surface-variant mb-2 uppercase tracking-wider">Description</label>
<div class="border border-outline-variant rounded-lg overflow-hidden bg-white">
<textarea id="event-description-editor" name="description" data-admin-tinymce class="w-full min-h-[200px] border-0 focus:ring-0 p-2 text-body-md" placeholder="Describe your event to potential attendees..." rows="8">{!! old('description', $e?->description ?? '') !!}</textarea>
</div>
@error('description')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
@include('admin.partials.rich-text-editor')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('event-description-editor');
    if (el && window.adminRichTextInit) {
        window.adminRichTextInit(el);
    }
});
</script>
@endpush
</div>
</div>
<div class="space-y-5 min-w-0 lg:col-span-4 lg:border-l lg:border-outline-variant/80 lg:pl-8 xl:pl-10">
@php
    $eventCategoriesList = $eventCategories ?? collect();
    $organizersEmpty = isset($organizers) && $organizers->isEmpty();
    $categoriesEmpty = $eventCategoriesList->isEmpty();
@endphp
@if(isset($organizers))
@include('admin.partials.event-field-card-start', ['cardTitle' => 'Organizer', 'cardAddNew' => 'organizer', 'cardShowAddNew' => ! $organizersEmpty])
@if($organizersEmpty)
<div id="event-organizer-empty" class="rounded-lg border border-outline-variant bg-amber-50 text-amber-950 dark:bg-amber-950/20 dark:text-amber-100 p-4">
<p class="text-body-sm font-medium mb-3">No organizers yet. Add one to assign a partner to this event.</p>
<button type="button" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white font-bold rounded-lg text-sm hover:opacity-90" data-quick-open-modal="organizer">Add organizer</button>
</div>
@endif
<div id="event-organizer-select-wrap" class="{{ $organizersEmpty ? 'hidden' : '' }}">
@php
    $organizerOptions = [['value' => '', 'label' => '— No organizer —']];
    foreach ($organizers as $org) {
        $organizerOptions[] = [
            'value' => (string) $org->id,
            'label' => $org->name.' — '.$org->company_name,
        ];
    }
@endphp
@include('partials.searchable-select', [
    'name' => 'organizer_id',
    'id' => 'event-organizer-select',
    'selected' => old('organizer_id', $e?->organizer_id),
    'options' => $organizerOptions,
    'searchPlaceholder' => 'Search organizer…',
    'triggerPlaceholder' => '— No organizer —',
    'inputClass' => $errors->has('organizer_id') ? 'border-error' : '',
])
@error('organizer_id')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
@include('admin.partials.event-field-card-end')
@endif
@include('admin.partials.event-field-card-start', ['cardTitle' => 'Category', 'cardAddNew' => 'category', 'cardShowAddNew' => ! $categoriesEmpty])
@if($categoriesEmpty)
<div id="event-category-empty" class="rounded-lg border border-outline-variant bg-amber-50 text-amber-950 dark:bg-amber-950/20 dark:text-amber-100 p-4">
<p class="text-body-sm font-medium mb-3">No event categories yet. Create one to classify this event.</p>
<button type="button" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white font-bold rounded-lg text-sm hover:opacity-90" data-quick-open-modal="category">Add category</button>
</div>
@endif
<div id="event-category-select-wrap" class="{{ $categoriesEmpty ? 'hidden' : '' }}">
@php
    $categoryOptions = [['value' => '', 'label' => '— Select —']];
    foreach ($eventCategoriesList as $cat) {
        $categoryOptions[] = [
            'value' => (string) $cat->id,
            'label' => $cat->name,
        ];
    }
@endphp
@include('partials.searchable-select', [
    'name' => 'event_category_id',
    'id' => 'event-category-select',
    'selected' => old('event_category_id', $e?->event_category_id),
    'options' => $categoryOptions,
    'searchPlaceholder' => 'Search category…',
    'triggerPlaceholder' => '— Select —',
    'inputClass' => $errors->has('event_category_id') ? 'border-error' : '',
])
@error('event_category_id')
<p class="text-error text-sm mt-1">{{ $message }}</p>
@enderror
</div>
@include('admin.partials.event-field-card-end')
@include('admin.chunks._event-basic-speakers-block', ['event' => $e, 'allSpeakers' => $allSpeakers ?? collect(), 'embedded' => true])
@include('admin.chunks._event-basic-location-block', ['event' => $e])
</div>
</div>
</div>
</div>
</div>
</div>
<footer class="fixed bottom-0 right-0 left-sidebar-width h-20 bg-white border-t border-outline-variant z-40 px-8 flex items-center justify-between shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
<div class="flex items-center gap-2 text-on-surface-variant">
<span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">check_circle</span>
<span class="text-body-md font-medium">@if($isEdit)Next: ticketing@else Create the event, then set up tickets @endif</span>
</div>
<div class="flex items-center gap-4 flex-wrap justify-end">
@if($isEdit)
@include('admin.chunks._event-wizard-seat-plan-link')
@include('admin.chunks._event-wizard-save-button', [
    'currentStep' => 1,
    'buttonClass' => 'px-6 py-2.5 rounded-lg border border-outline-variant text-on-surface font-bold text-body-md hover:bg-surface-container-low transition-colors active:scale-95',
    'useFormnovalidate' => false,
])
<button type="submit" name="wizard_action" value="continue" class="px-8 py-2.5 rounded-lg bg-primary text-white font-bold text-body-md flex items-center gap-2 hover:bg-primary-container shadow-lg shadow-primary/20 transition-all active:scale-95">
Next: Ticketing
<span class="material-symbols-outlined text-[18px]">arrow_forward</span>
</button>
@else
<button type="submit" class="px-8 py-2.5 rounded-lg bg-primary text-white font-bold text-body-md flex items-center gap-2 hover:bg-primary-container shadow-lg shadow-primary/20 transition-all active:scale-95">
Continue
<span class="material-symbols-outlined text-[18px]">arrow_forward</span>
</button>
@endif
</div>
</footer>
</form>
@include('admin.chunks._event-wizard-quick-add-modals')
