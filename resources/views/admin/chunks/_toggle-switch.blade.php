@php
  $name = $name ?? '';
  $value = $value ?? '1';
  $checked = filter_var($checked ?? false, FILTER_VALIDATE_BOOLEAN);
  $id = $id ?? null;
  $inputClass = $inputClass ?? '';
  $includeHidden = $includeHidden ?? true;
  $hiddenValue = $hiddenValue ?? '0';
@endphp
@if($includeHidden)
<input type="hidden" name="{{ $name }}" value="{{ $hiddenValue }}"/>
@endif
<label class="relative inline-flex cursor-pointer items-center shrink-0">
<input
  type="checkbox"
  name="{{ $name }}"
  value="{{ $value }}"
  @if($id) id="{{ $id }}" @endif
  class="peer sr-only {{ $inputClass }}"
  @checked($checked)
  @if(!empty($disabled)) disabled @endif
/>
<span class="peer relative h-6 w-12 rounded-full bg-surface-container-highest after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition-all peer-checked:bg-emerald-500 peer-checked:after:translate-x-6 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500/30 peer-focus-visible:ring-offset-2 peer-disabled:opacity-45 peer-disabled:cursor-not-allowed" aria-hidden="true"></span>
</label>
