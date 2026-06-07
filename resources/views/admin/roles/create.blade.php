@extends('admin.layouts.app')

@section('title', 'Create Role')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search roles...'])
<div class="pt-16 pb-24">
@include('admin.chunks._role-form-main', ['role' => $role, 'groupedModules' => $groupedModules])
</div>
@endsection
