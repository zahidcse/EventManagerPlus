@php
  $name = $name ?? 'timezone';
  $selected = old($name, $selected ?? 'UTC');
  $id = $id ?? $name;
  $required = $required ?? false;
  $allowEmpty = $allowEmpty ?? false;
  $class = $class ?? '';
@endphp
<select name="{{ $name }}" id="{{ $id }}" @if($required) required @endif class="{{ $class }}">
  @if($allowEmpty)
    <option value="" @selected($selected === '' || $selected === null)>Use each event's timezone</option>
  @endif
  @foreach(\App\Support\TimezoneList::groupedForSelect() as $region => $zones)
    <optgroup label="{{ $region }}">
      @foreach($zones as $zone)
        <option value="{{ $zone }}" @selected($selected === $zone)>{{ \App\Support\TimezoneList::label($zone) }}</option>
      @endforeach
    </optgroup>
  @endforeach
</select>
