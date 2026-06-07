@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
@include('admin.chunks._dashboard-main', ['overview' => $overview])
@endsection
