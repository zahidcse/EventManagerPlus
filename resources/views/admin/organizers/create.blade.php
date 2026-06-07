@extends('admin.layouts.app')

@section('title', 'Create Organizer')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search organizers, events, or reports...'])
<div class="pt-16 pb-24 admin-has-fixed-footer">
@include('admin.chunks._organizer-create-main')
</div>
@endsection
