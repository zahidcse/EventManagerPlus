@extends('public.classic.layouts.app')

@section('title', 'Pay · '.$siteName)

@section('content')
<div style="max-width:520px;margin:40px auto;padding:0 16px;font-family:inherit">
  <a href="{{ route('events.show', $event) }}" style="display:inline-block;margin-bottom:20px;color:inherit;text-decoration:underline">← Back to {{ Str::limit($event->title, 48) }}</a>
  <h1 style="font-size:1.5rem;margin:0 0 12px;line-height:1.2">Secure payment</h1>
  <p style="opacity:.85;font-size:14px;margin:0 0 24px;line-height:1.45">
    {{ $siteName }} — {{ Str::limit($event->title, 60) }}. Complete payment via Razorpay (cards, UPI, wallets, netbanking where supported).
  </p>
  <div style="margin-bottom:20px;font-size:15px;font-weight:600">
    Amount: {{ $currency }} {{ number_format($amountCents / 100, ($amountCents % 100) === 0 ? 0 : 2, '.', '') }}
  </div>

  @if(session('book_error'))
    <div class="alert-error" role="alert" style="margin-bottom:16px">{{ session('book_error') }}</div>
  @endif

  <button type="button" id="rzp-pay-btn" class="btn">Pay with Razorpay</button>
  <p id="rzp-status" style="margin-top:12px;font-size:13px;opacity:.8"></p>

  <form method="post" action="{{ $verifyUrl }}" id="rzp-verify-form" style="display:none">
    @csrf
    <input type="hidden" name="checkout_id" value="{{ $checkout->id }}" />
    <input type="hidden" name="razorpay_order_id" id="rzpf_order_id" />
    <input type="hidden" name="razorpay_payment_id" id="rzpf_payment_id" />
    <input type="hidden" name="razorpay_signature" id="rzpf_signature" />
  </form>
</div>
@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function () {
  var cancelUrl = @json($cancelUrl);

  document.getElementById('rzp-pay-btn').addEventListener('click', function () {
    var opts = {
      key: @json($razorpayKeyId),
      order_id: @json($razorpayOrderId),
      name: @json(Str::limit($siteName, 40)),
      description: @json(Str::limit('Tickets: '.$event->title, 60)),
      currency: @json($currency),
      notes: {},
      modal: {
        ondismiss: function () {
          window.location.href = cancelUrl;
        }
      },
      prefill: {
        name: @json(Str::limit($contactName ?: '', 50)),
        email: @json($contactEmail ?: ''),
        contact: @json(preg_replace('/\D+/', '', (string) $contactPhone))
      },
      theme: {
        color: '#2563eb'
      },
      handler: function (response) {
        document.getElementById('rzpf_order_id').value = response.razorpay_order_id;
        document.getElementById('rzpf_payment_id').value = response.razorpay_payment_id;
        document.getElementById('rzpf_signature').value = response.razorpay_signature;
        document.getElementById('rzp-verify-form').submit();
      }
    };
    var rz = new Razorpay(opts);
    rz.open();
  });
})();
</script>
@endpush
