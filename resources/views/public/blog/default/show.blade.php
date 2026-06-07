@extends('public.layouts.frontend-default')

@section('title', ($post->meta_title ?: $post->title).' — '.$siteName)
@section('meta_description')
{{ \Illuminate\Support\Str::limit(trim($post->meta_description ?: strip_tags($post->excerpt ?: $post->body ?: '')), 155) }}
@endsection

@push('head')
@if($post->hero_image_path)
<meta property="og:image" content="{{ asset('uploads/'.$post->hero_image_path) }}" />
@endif
@endpush

@section('content')
  <a class="back" href="{{ route('blog.index') }}">← All posts</a>

  @php
    $hero = $post->hero_image_path ? asset('uploads/'.$post->hero_image_path) : null;
    $when = $post->published_at ? $post->published_at->format('l, F j, Y') : $post->updated_at->format('l, F j, Y');
  @endphp

  @if($hero)
  <div class="hero blog-detail-hero">
    <img src="{{ $hero }}" alt="" />
    <div class="overlay"></div>
    <div class="hero-content">
      <span class="badge hero-badge">Blog</span>
      <h1>{{ $post->title }}</h1>
      <p class="blog-detail-meta">{{ $when }}</p>
    </div>
  </div>
  @else
  <header style="margin-bottom:28px;padding-top:8px">
    <p class="kicker">Blog</p>
    <h1 class="title">{{ $post->title }}</h1>
    <p class="blog-detail-meta">{{ $when }}</p>
  </header>
  @endif

  <article class="blog-article-block">
    <div class="blog-body body-text">
      {!! $bodyHtml !!}
    </div>
  </article>
@endsection
