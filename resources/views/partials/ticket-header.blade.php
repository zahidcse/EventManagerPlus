@php
  $show = static fn (string $k): bool => (bool) ($pdfFields[$k] ?? true);
  $logoSrc = ! empty($forPdf) ? ($branding['logoDataUri'] ?? null) : ($branding['logoUrl'] ?? null);
  $organizerName = $branding['organizerName'] ?? '';
  $companyName = $branding['companyName'] ?? '';
  $hasHeader = ($show('company_logo') && $logoSrc)
    || $show('event_name')
    || ($show('organizer_name') && $organizerName !== '')
    || ($show('company_name') && $companyName !== '');
@endphp

@if($hasHeader)
  <table class="ticket-header" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 16px;">
    <tr>
      @if($show('company_logo') && $logoSrc)
        <td width="130" style="vertical-align: middle; padding-right: 16px;">
          <img src="{{ $logoSrc }}" alt="" style="max-height: 56px; max-width: 120px; display: block;" />
        </td>
      @endif
      <td style="vertical-align: middle;">
        @if($show('event_name'))
          <h1 style="margin: 0 0 4px; font-size: {{ ! empty($forPdf) ? '20px' : '22px' }};">{{ $event->title }}</h1>
        @endif
        @if($show('organizer_name') && $organizerName !== '')
          <p style="margin: 0; font-size: {{ ! empty($forPdf) ? '12px' : '14px' }}; font-weight: 600; color: #333;">{{ $organizerName }}</p>
        @endif
        @if($show('company_name') && $companyName !== '')
          <p class="muted" style="margin: {{ ($show('organizer_name') && $organizerName !== '') ? '4px' : '0' }} 0 0; font-size: {{ ! empty($forPdf) ? '11px' : '13px' }};">{{ $companyName }}</p>
        @endif
      </td>
    </tr>
  </table>
@endif
