@extends('public.layouts.classic')

@section('title', ($page->meta_title ?: $page->title).' — '.$siteName)
@section('meta_description')
{{ \Illuminate\Support\Str::limit(trim($page->meta_description ?: strip_tags($page->body ?: '')), 155) }}
@endsection

@push('head')
@if($page->hero_image_path)
<meta property="og:image" content="{{ asset('uploads/'.$page->hero_image_path) }}" />
@endif
@endpush

@section('content')
  <a class="back" href="{{ url('/') }}">← Home</a>

  @php
    $hero = $page->hero_image_path ? asset('uploads/'.$page->hero_image_path) : null;
    $when = $page->published_at ? $page->published_at->format('l, F j, Y') : null;
  @endphp

  @if($hero)
  <div class="hero blog-detail-hero">
    <img src="{{ $hero }}" alt="" />
    <div class="overlay"></div>
    <div class="hero-content">
      <span class="badge hero-badge">Page</span>
      <h1>{{ $page->title }}</h1>
      @if($when)
      <p class="blog-detail-meta">{{ $when }}</p>
      @endif
    </div>
  </div>
  @else
  <header class="blog-article-head-inline">
    <span class="badge hero-badge" style="margin-bottom:16px;display:inline-flex">Page</span>
    <h1 class="title" style="margin-bottom:12px">{{ $page->title }}</h1>
    @if($when)
    <p class="blog-detail-meta">{{ $when }}</p>
    @endif
  </header>
  @endif

  <article class="blog-article-block">
    <div class="blog-body body-text">
      {!! $bodyHtml !!}
    </div>
  </article>
@endsection
