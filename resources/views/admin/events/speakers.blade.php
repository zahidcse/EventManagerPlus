@extends('admin.layouts.app')

@section('title', 'Event speakers')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search events...'])
<div class="pt-16 pb-16">
@include('admin.chunks._event-speakers-main')
</div>
@endsection
