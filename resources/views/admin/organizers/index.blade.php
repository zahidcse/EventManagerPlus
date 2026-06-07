@extends('admin.layouts.app')

@section('title', 'Organizers')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search organizers, companies, or emails...'])
<main class="mt-16 p-8 min-h-screen pb-12">
@include('admin.chunks._organizers-index-main')
</main>
@endsection

@push('scripts')
  <script>
    (function () {
      document.querySelectorAll('details.organizer-row-actions').forEach(function (el) {
        el.addEventListener('toggle', function () {
          if (el.open) {
            document.querySelectorAll('details.organizer-row-actions').forEach(function (other) {
              if (other !== el) {
                other.removeAttribute('open');
              }
            });
          }
        });
      });

      document.addEventListener('click', function (e) {
        if (!e.target.closest('details.organizer-row-actions')) {
          document.querySelectorAll('details.organizer-row-actions[open]').forEach(function (d) {
            d.removeAttribute('open');
          });
        }
      });
    })();
  </script>
@endpush
