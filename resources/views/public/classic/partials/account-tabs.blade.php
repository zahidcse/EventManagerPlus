<nav class="account-tabs" aria-label="Account sections">
  <a href="{{ route('account.index') }}" class="account-tab {{ request()->routeIs('account.index') || request()->routeIs('account.bookings.order') ? 'is-active' : '' }}">My account</a>
  <a href="{{ route('account.profile') }}" class="account-tab {{ request()->routeIs('account.profile') ? 'is-active' : '' }}">Profile</a>
</nav>
