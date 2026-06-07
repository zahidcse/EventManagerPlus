@php
    /** @var int $currentStep 1-4 */
    $currentStep = max(1, min(4, (int) ($currentStep ?? 1)));
    $progressClass = match ($currentStep) {
        1 => 'w-1/4',
        2 => 'w-2/4',
        3 => 'w-3/4',
        default => 'w-full',
    };
    $step1Url = isset($event) && $event
        ? route('admin.events.edit', $event)
        : route('admin.events.create');
    $canLinkSteps = isset($event) && $event;
@endphp

<div class="mb-4 sm:mb-5 py-1" role="navigation" aria-label="Event setup steps">
<div class="flex items-center justify-between relative max-w-4xl mx-auto px-1">
<div class="absolute top-4 left-0 right-0 h-0.5 bg-surface-container-highest -z-0 rounded-full"></div>
<div class="absolute top-4 left-0 {{ $progressClass }} h-0.5 bg-primary -z-0 rounded-full transition-[width] duration-300 ease-out"></div>
@foreach ([
    1 => ['label' => 'Basic Info', 'url' => $step1Url],
    2 => ['label' => 'Ticketing', 'url' => $canLinkSteps ? route('admin.events.edit.tickets', $event) : null],
    3 => ['label' => 'Content', 'url' => $canLinkSteps ? route('admin.events.edit.content', $event) : null],
    4 => ['label' => 'Advanced', 'url' => $canLinkSteps ? route('admin.events.edit.content', ['event' => $event, 'panel' => 'advanced']) : null],
] as $num => $meta)
@php
    $isDone = $num < $currentStep;
    $isCurrent = $num === $currentStep;
    $url = $meta['url'];
    $isClickable = $url && ($isDone || ($num !== $currentStep));
@endphp
<div class="relative z-10 flex flex-col items-center w-0 flex-1 min-w-0">
@if($isClickable && ! $isCurrent)
<a href="{{ $url }}" class="group flex flex-col items-center no-underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 rounded-xl max-w-full">
@else
<div class="flex flex-col items-center max-w-full">
@endif
<div class="w-9 h-9 rounded-lg flex items-center justify-center font-bold text-sm transition-all
@if($isDone || $isCurrent)
bg-primary text-white shadow-md shadow-primary/25
@else
bg-surface-container-highest text-on-surface-variant border-2 border-surface
@endif
@if($isCurrent)
ring-2 ring-primary/30 ring-offset-1 ring-offset-white
@endif
">
@if($isDone && ! $isCurrent)
<span class="material-symbols-outlined text-[18px]">check</span>
@else
{{ $num }}
@endif
</div>
<span
@class([
    'mt-1.5 text-center text-[10px] sm:text-xs max-w-[4.5rem] sm:max-w-none leading-tight px-0.5',
    'font-bold text-primary' => $isCurrent,
    'font-semibold text-primary' => $isDone && ! $isCurrent,
    'font-medium text-on-surface-variant' => ! $isCurrent && ! $isDone,
])
@if($isCurrent) aria-current="step" @endif
>{{ $meta['label'] }}</span>
@if($isClickable && ! $isCurrent)
</a>
@else
</div>
@endif
</div>
@endforeach
</div>
</div>
