@extends('admin.layouts.app')



@section('title', $event ? 'Edit Event' : 'Create Event')



@section('content')

@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search events, venues, or orders...'])

<div class="pt-16">

@include('admin.chunks._event-create-main')

</div>

@endsection

