@extends('admin.layouts.app')

@section('title', 'Ticketing')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search events, tickets, or orders...'])
<div class="pt-16 pb-28">
@include('admin.chunks._event-tickets-main')
</div>
@endsection
