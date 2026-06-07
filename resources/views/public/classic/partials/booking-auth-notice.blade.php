@guest
<div class="book-auth-guest-callout">
  <p class="book-auth-callout-title">Booking as a guest</p>
  <p class="book-auth-callout-body"><a href="{{ route('login', ['redirect' => $bookingAuthReturnPath]) }}">Sign in</a> to reuse your saved name and email, or <a href="{{ route('register', ['redirect' => $bookingAuthReturnPath]) }}">create a free account</a> for faster checkout later.</p>
</div>
@else
  @unless(Auth::user()->is_admin)
    <p class="book-auth-signed-in-note">You’re signed in as <strong>{{ Auth::user()->name }}</strong>. Your details are prefilled — you can edit them if needed.</p>
  @endunless
@endguest
