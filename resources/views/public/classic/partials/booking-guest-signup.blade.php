@guest
<div id="bookGuestSignup" class="book-guest-signup" aria-live="polite">
  <div class="book-guest-signup-inner">
    <label class="book-guest-signup-check">
      <input type="checkbox" name="create_account" id="create_account_checkbox" value="1" @checked(old('create_account')) />
      <span>Create an account with this booking for faster checkout later</span>
    </label>
    <p class="book-guest-signup-hint book-guest-signup-hint--pay" id="bookGuestSignupPayHint" hidden>With online payment, tick this to create your account before checkout so your booking appears under My account after you pay.</p>
    <div id="bookGuestSignupPwFields" class="book-guest-signup-pw" @unless(filled(old('create_account'))) hidden @endunless>
      <label for="signup_password">{{ __('Password') }}</label>
      <input id="signup_password" name="password" type="password" autocomplete="new-password" />

      <label for="signup_password_confirmation">{{ __('Confirm password') }}</label>
      <input id="signup_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
    </div>
  </div>
</div>

<script>
(function() {
  var chk = document.getElementById('create_account_checkbox');
  var pw = document.getElementById('bookGuestSignupPwFields');
  if (!chk || !pw) return;
  function syncPwVisibility() {
    if (chk.checked) {
      pw.removeAttribute('hidden');
    } else {
      pw.setAttribute('hidden', 'hidden');
      var p1 = document.getElementById('signup_password');
      var p2 = document.getElementById('signup_password_confirmation');
      if (p1) p1.value = '';
      if (p2) p2.value = '';
    }
  }
  chk.addEventListener('change', syncPwVisibility);
  syncPwVisibility();
})();
</script>
@endguest
