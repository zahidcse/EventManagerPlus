@extends('admin.layouts.app')

@section('title', 'Edit Staff')

@section('content')
@include('admin.partials.admin-topbar', ['searchPlaceholder' => 'Search staff, roles, or modules...'])
<div class="pt-16 pb-24">
@include('admin.chunks._staff-form-main', ['staffUser' => $staffUser, 'roles' => $roles])
</div>
@endsection
