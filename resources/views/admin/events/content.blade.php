@extends('admin.layouts.app')

@section('title', ($wizardPanel ?? 'content') === 'advanced' ? 'Event Advanced Settings' : 'Event Content')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search events...'])
<div class="pt-16 pb-28">
@if(($wizardPanel ?? 'content') === 'advanced')
@include('admin.chunks._event-advanced-main')
@else
@include('admin.chunks._event-content-main')
@endif
</div>
@endsection
