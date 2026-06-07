@extends('admin.layouts.app')

@section('title', 'Speakers')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search...'])
<main class="mt-16 p-8 min-h-screen">
@include('admin.chunks._speakers-index-main')
</main>
@endsection
