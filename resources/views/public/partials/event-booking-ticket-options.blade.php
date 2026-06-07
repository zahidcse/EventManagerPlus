@php
  $perDayDateStr = $perDayDate ?? null;
  $isPerDay = is_string($perDayDateStr) && $perDayDateStr !== '';
@endphp
<div class="option-list ep-ticket-options">
  @forelse($event->tickets as $ticket)
    @php
      $rem = $ticket->remainingForSale($perDayDateStr);
      $maxPick = $rem !== null ? min(100, $rem) : 100;
      if ($isPerDay) {
        $oldQty = (int) old('qty_by_date.'.$perDayDateStr.'.'.$ticket->id, 0);
        $qtyName = 'qty_by_date['.$perDayDateStr.']['.$ticket->id.']';
      } else {
        $oldQty = (int) old('qty.'.$ticket->id, 0);
        $qtyName = 'qty['.$ticket->id.']';
      }
      $bookable = $ticket->isBookableNow($perDayDateStr);
    @endphp
    <div class="option" data-kind="ticket" data-name="{{ $ticket->name }}" data-price="{{ number_format($ticket->effectiveUnitPrice(), 2, '.', '') }}">
      <div class="info">
        <div class="name">{{ $ticket->name }}</div>
        <div class="desc">
          @if(! $ticket->isWithinSalesWindow())
            Outside the current sales window.
          @elseif($rem !== null && $rem <= 0)
            Sold out.
          @else
            @if($rem !== null){{ $rem }} left.@endif
            @if($event->usesGlobalTicketQuantity() && $rem !== null && $rem > 0)
              <span class="ep-shared-pool-hint"> Shared across all ticket types.</span>
            @endif
            @if($ticket->sales_start || $ticket->sales_end)
              Sales
              @if($ticket->sales_start) from {{ $ticket->sales_start->format('M j') }}@endif
              @if($ticket->sales_end) until {{ $ticket->sales_end->format('M j') }}@endif.
            @endif
          @endif
        </div>
        <div class="price-tag">
          @if($ticket->early_bird_price !== null && $ticket->early_bird_ends_at && now()->startOfDay()->lte($ticket->early_bird_ends_at))
            <span style="text-decoration:line-through;color:var(--ep-muted);font-weight:500;margin-right:8px">${{ number_format((float) $ticket->price, 0) }}</span>
          @endif
          ${{ number_format($ticket->effectiveUnitPrice(), floor($ticket->effectiveUnitPrice()) == $ticket->effectiveUnitPrice() ? 0 : 2) }}
        </div>
      </div>
      @if($bookable)
      <div class="qty">
        <button type="button" class="minus" onclick="changeQty(this,-1)" @if($oldQty < 1) disabled @endif>-</button>
        <span class="count">{{ $oldQty }}</span>
        <button type="button" class="plus" onclick="changeQty(this,1)" @if($oldQty >= $maxPick) disabled @endif>+</button>
        <input type="hidden" class="qty-hidden" name="{{ $qtyName }}" value="{{ $oldQty }}" data-max="{{ $maxPick }}" autocomplete="off" />
      </div>
      <div class="attendee-ticket-inline" data-attendee-inline style="display:none">
        <div class="attendee-ticket-grid attendee-ticket-grid--inline"></div>
      </div>
      @endif
    </div>
  @empty
    <p class="body-text">Tickets are not configured for this event yet.</p>
  @endforelse
</div>

@if($event->additionalServices->isNotEmpty())
<h3 class="ep-subhead-addons" style="margin:18px 0 8px;font-size:16px;font-weight:700">{{ $isPerDay ? 'Add-ons (this day)' : 'Additional services' }}</h3>
@endif
<div class="option-list ep-addon-options" style="margin-top:0">
  @foreach($event->additionalServices as $svc)
    @php
      if ($isPerDay) {
        $oldA = (int) old('addon_qty_by_date.'.$perDayDateStr.'.'.$svc->id, 0);
        $addonName = 'addon_qty_by_date['.$perDayDateStr.']['.$svc->id.']';
      } else {
        $oldA = (int) old('addon_qty.'.$svc->id, 0);
        $addonName = 'addon_qty['.$svc->id.']';
      }
      $addonRem = $svc->remainingForSale();
      $addonMax = $addonRem !== null ? min(50, $addonRem) : 50;
      $addonSoldOut = $addonRem !== null && $addonRem <= 0;
    @endphp
    <div class="option" data-kind="addon" data-name="{{ $svc->name }}" data-price="{{ number_format((float) $svc->price, 2, '.', '') }}">
      <div class="info">
        <div class="name">{{ $svc->name }}</div>
        <div class="desc">
          @if($addonSoldOut)
            Sold out.
          @elseif($addonRem !== null)
            {{ $addonRem }} left.
          @endif
        </div>
        <div class="price-tag">+${{ number_format((float) $svc->price, floor((float) $svc->price) == (float) $svc->price ? 0 : 2) }}</div>
      </div>
      @if(! $addonSoldOut)
      <div class="qty">
        <button type="button" class="minus" onclick="changeQty(this,-1)" @if($oldA < 1) disabled @endif>-</button>
        <span class="count">{{ $oldA }}</span>
        <button type="button" class="plus" onclick="changeQty(this,1)" @if($oldA >= $addonMax) disabled @endif>+</button>
        <input type="hidden" class="qty-hidden" name="{{ $addonName }}" value="{{ $oldA }}" data-max="{{ $addonMax }}" autocomplete="off" />
      </div>
      @endif
    </div>
  @endforeach
</div>

