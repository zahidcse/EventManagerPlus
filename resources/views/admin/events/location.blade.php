@extends('admin.layouts.app')

@section('title', 'Event Location')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search events, locations, or organizers...'])
<div class="pt-16 pb-16">
@include('admin.chunks._event-location-main')
</div>
@endsection
