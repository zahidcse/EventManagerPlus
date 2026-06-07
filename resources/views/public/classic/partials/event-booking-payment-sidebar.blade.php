@php
  $pmStripe = !empty($showStripePayments);
  $pmPayPal = !empty($showPayPalPayments);
  $pmRazorpay = !empty($showRazorpayPayments);
  $pmSslcommerz = !empty($showSslCommerzPayments);
  $pmCash = !empty($showCashOfflinePayments);
  $pmBank = !empty($showBankOfflinePayments);
  $pmOffline = $pmCash || $pmBank;
  $instructions = trim((string) ($bankTransferInstructions ?? ''));
@endphp

@if($pmStripe || $pmPayPal || $pmRazorpay || $pmSslcommerz || $pmOffline)
  @php
    $methods = [];
    if ($pmStripe) {
        $methods[] = ['value' => 'stripe', 'label' => 'Credit / debit card (Stripe)'];
    }
    if ($pmPayPal) {
        $methods[] = ['value' => 'paypal', 'label' => 'PayPal'];
    }
    if ($pmRazorpay) {
        $methods[] = ['value' => 'razorpay', 'label' => 'Razorpay (card, UPI, wallets…)'];
    }
    if ($pmSslcommerz) {
        $methods[] = ['value' => 'sslcommerz', 'label' => 'SSLCommerz (BD cards, mobile & net banking)'];
    }
    if ($pmCash) {
        $methods[] = ['value' => 'cash', 'label' => 'Cash payment'];
    }
    if ($pmBank) {
        $methods[] = ['value' => 'bank_transfer', 'label' => 'Bank transfer'];
    }
    $methodValues = array_column($methods, 'value');
    $oldPm = old('payment_method');
    $picked = in_array((string) $oldPm, $methodValues, true) ? (string) $oldPm : null;
    if ($picked === null && !empty($methodValues)) {
        $picked = $methodValues[0];
    }
  @endphp
  <div class="payment-methods-block" id="paymentMethodsBlock" style="display:none;margin-top:12px">
    <div class="label" style="font-size:12px;margin-bottom:8px">Payment</div>
    @if(count($methods) > 1)
      <div class="pay-options" style="display:flex;flex-direction:column;gap:8px;font-size:14px">
        @foreach($methods as $m)
          <label class="pay-option" style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;line-height:1.35">
            <input type="radio" name="payment_method" value="{{ $m['value'] }}" @checked(old('payment_method', $picked) === $m['value']) />
            <span>{{ $m['label'] }}</span>
          </label>
        @endforeach
      </div>
      <p style="font-size:11px;margin:8px 0 0;line-height:1.4;opacity:.85">Applies when your order total is above zero.</p>
    @elseif(count($methods) === 1)
      <input type="hidden" name="payment_method" value="{{ $methods[0]['value'] }}" />
      <p style="font-size:12px;margin:0;line-height:1.4;opacity:.9">
        @if($methods[0]['value'] === 'stripe')
          Secure checkout with Stripe when your order total is above zero.
        @elseif($methods[0]['value'] === 'paypal')
          You will complete payment with PayPal when your order total is above zero.
        @elseif($methods[0]['value'] === 'razorpay')
          You will complete payment with Razorpay when your order total is above zero.
        @elseif($methods[0]['value'] === 'sslcommerz')
          You will pay through SSLCommerz when your order total is above zero.
        @elseif($methods[0]['value'] === 'cash')
          Cash payment when your order total is above zero. Add your receipt or reference below.
        @else
          Bank transfer when your order total is above zero. Use the reference field after paying.
        @endif
      </p>
    @endif

    @if($pmCash || $pmBank)
      <div id="offlinePaymentFields" class="offline-payment-fields" style="margin-top:12px;display:none">
        @if($instructions !== '')
          <div id="bankTransferInstructions" style="display:none;margin-bottom:10px;font-size:12px;line-height:1.45;color:var(--ep-on-surface,#2a272e);opacity:.92">
            <strong>Bank transfer details</strong>
            <div style="margin-top:6px;white-space:pre-wrap">{{ $instructions }}</div>
          </div>
        @endif
        <label for="offline_payment_reference" style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">
          Payment reference
        </label>
        <input id="offline_payment_reference" type="text" name="offline_payment_reference" maxlength="191" autocomplete="transaction-identifier"
          value="{{ old('offline_payment_reference') }}"
          placeholder="Transaction ID, receipt #, deposit ref…"
          style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid var(--ep-outline,var(--outline,#ccc));background:var(--ep-surface,#fff);font-size:14px" />
        <p style="font-size:11px;margin:6px 0 0;line-height:1.35;opacity:.8">Shown to the organizer — use the same reference you used when paying.</p>
      </div>
    @endif
  </div>
@endif
