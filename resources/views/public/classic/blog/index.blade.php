@extends('public.layouts.classic')

@php
    $listDescription = 'Stories, updates, and guides from the team.';
@endphp

@section('title', 'Blog — '.$siteName)
@section('meta_description', $listDescription)

@section('content')
@php
    $placeholders = [
        'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800&q=80',
        'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=800&q=80',
        'https://images.unsplash.com/photo-1432821596592-e2c18b781227?w=800&q=80',
    ];
@endphp

  <div class="page-head">
    <p class="kicker">Blog</p>
    <h1 class="title">Latest articles</h1>
    <p class="lede">{{ $listDescription }}</p>
  </div>

  <form class="search classic-search-wrap" method="get" action="{{ route('blog.index') }}">
    <div class="search-field"><i data-lucide="search" class="icon-sm" style="color:#a39bb8"></i><input type="search" name="q" placeholder="Search posts…" value="{{ request('q') }}" /></div>
    <button type="submit" class="btn btn-primary">Search</button>
  </form>

  <div class="events" style="margin-top:40px">
    @forelse($posts as $idx => $post)
      @php
        $img = $post->hero_image_path ? asset('uploads/'.$post->hero_image_path) : ($placeholders[$idx % count($placeholders)] ?? $placeholders[0]);
        $when = $post->published_at ? $post->published_at->format('M j, Y') : $post->updated_at->format('M j, Y');
        $summary = $post->excerpt ?: \Illuminate\Support\Str::limit(trim(strip_tags($post->body ?? '')), 140);
      @endphp
      <a href="{{ route('blog.show', $post) }}" class="event" style="text-decoration:none;color:inherit;display:block">
        <div class="event-img">
          <img src="{{ $img }}" alt="{{ $post->title }}" loading="lazy" />
          <span class="event-tag">Article</span>
        </div>
        <div class="event-body">
          <h3>{{ $post->title }}</h3>
          <div class="event-meta"><i data-lucide="calendar" class="icon-sm"></i> {{ $when }}</div>
          @if($summary !== '')
          <p style="margin-top:12px;font-size:14px;color:var(--muted);line-height:1.5">{{ $summary }}</p>
          @endif
          <div class="event-foot"><span class="event-price text-gradient">Read more</span><span class="btn btn-primary" style="pointer-events:none">View post</span></div>
        </div>
      </a>
    @empty
      <p style="grid-column:1/-1;color:var(--muted);text-align:center;padding:48px 16px">No published posts yet. Check back soon.</p>
    @endforelse
  </div>

{{ $posts->withQueryString()->onEachSide(1)->links('pagination.ep') }}
@endsection
