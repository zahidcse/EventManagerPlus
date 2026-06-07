@extends($accountLayout)

@section('title')
  My account — {{ $siteName }}
@endsection

@section('meta_description')
  View your event bookings and ticket history.
@endsection

@section('content')
  <div class="container account-shell">
    @include('public.partials.account-tabs')

    <div class="account-panel">
      <h1 class="account-heading">My account</h1>
      <p class="account-intro">Bookings linked to your signed-in email appear here.</p>

      @if($bookings->isEmpty())
        <p class="account-empty">No bookings yet. <a href="{{ route('events.index') }}">Browse events</a> to get tickets.</p>
      @else
        @include('public.partials.account-booking-list')
        <div class="account-pagination">{{ $bookings->links() }}</div>
      @endif
    </div>
  </div>
@endsection
