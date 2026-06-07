@extends('admin.layouts.app')

@section('title', 'Staff')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search staff by name or email...'])
<main class="mt-16 p-8 min-h-screen pb-12">
@include('admin.chunks._staff-index-main')
</main>
@endsection
