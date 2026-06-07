@extends('admin.layouts.app')

@section('title', 'Event Media')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search events, locations, or organizers...'])
<div class="pt-16 pb-16">
@include('admin.chunks._event-media-main')
</div>
@endsection
