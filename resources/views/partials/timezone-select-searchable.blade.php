@php
  use App\Support\TimezoneList;
  $name = $name ?? 'timezone';
  $allowEmpty = $allowEmpty ?? false;
  $frontend = $frontend ?? false;
  $rawSelected = old($name, $selected ?? ($allowEmpty ? '' : 'UTC'));
  $selected = ($allowEmpty && ($rawSelected === '' || $rawSelected === null))
    ? ''
    : TimezoneList::normalize($rawSelected);
  $id = $id ?? $name.'-searchable';
  $required = $required ?? false;
  $class = $class ?? ($frontend ? 'account-timezone-select' : 'w-full max-w-xl');
  $timezoneOptions = array_map(static fn (array $opt): array => [
      'value' => $opt['value'],
      'label' => $opt['label'],
      'group' => $opt['region'],
  ], TimezoneList::searchOptions());
  if ($allowEmpty) {
      array_unshift($timezoneOptions, [
          'value' => '',
          'label' => "Use each event's timezone",
          'group' => '',
      ]);
  }
@endphp
@include('partials.searchable-select', [
  'name' => $name,
  'id' => $id,
  'selected' => $selected,
  'options' => $timezoneOptions,
  'searchPlaceholder' => 'Search timezone…',
  'triggerPlaceholder' => $allowEmpty ? "Use each event's timezone" : 'Select timezone…',
  'required' => $required,
  'class' => $class,
  'frontend' => $frontend,
])
