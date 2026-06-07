@extends('admin.layouts.app')

@section('title', 'Roles')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search roles...'])
<main class="mt-16 p-8 min-h-screen pb-12">
@include('admin.chunks._roles-index-main')
</main>
@endsection
