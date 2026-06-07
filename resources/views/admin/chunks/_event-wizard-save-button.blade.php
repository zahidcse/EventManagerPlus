@php
    $stepTitle = $stepTitle ?? match (max(1, min(4, (int) ($currentStep ?? 1)))) {
        1 => 'Basic Info',
        2 => 'Ticketing',
        3 => 'Content',
        4 => 'Advanced',
        default => 'Basic Info',
    };
    $formId = $formId ?? null;
    $buttonClass = $buttonClass ?? 'px-5 py-2.5 rounded-lg border border-outline-variant text-on-surface font-bold hover:bg-surface-container-low transition-colors active:scale-95';
    $useFormnovalidate = $useFormnovalidate ?? true;
@endphp
<button
  type="submit"
  @if($formId) form="{{ $formId }}" @endif
  name="wizard_action"
  value="draft"
  @if($useFormnovalidate) formnovalidate @endif
  class="{{ $buttonClass }}"
>
  Save {{ $stepTitle }}
</button>
