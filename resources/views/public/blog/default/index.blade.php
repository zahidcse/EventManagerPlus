@extends('public.layouts.frontend-default')

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

  <form class="search-bar" method="get" action="{{ route('blog.index') }}">
    <input type="search" name="q" placeholder="Search posts…" value="{{ request('q') }}" />
    <button type="submit" class="btn">Search</button>
  </form>

  <div class="grid">
    @forelse($posts as $idx => $post)
      @php
        $img = $post->hero_image_path ? asset('uploads/'.$post->hero_image_path) : ($placeholders[$idx % count($placeholders)] ?? $placeholders[0]);
        $when = $post->published_at ? $post->published_at->format('M j, Y') : $post->updated_at->format('M j, Y');
        $summary = $post->excerpt ?: \Illuminate\Support\Str::limit(trim(strip_tags($post->body ?? '')), 120);
      @endphp
      <a class="event-card" href="{{ route('blog.show', $post) }}">
        <div class="img-wrap">
          <img src="{{ $img }}" alt="{{ $post->title }}" loading="lazy" />
          <span class="badge">Article</span>
        </div>
        <div class="body">
          <h3>{{ $post->title }}</h3>
          <div class="meta">
            <div class="meta-row"><span aria-hidden="true">📅</span> {{ $when }}</div>
          </div>
          @if($summary !== '')
          <p class="body-text" style="margin-top:10px;font-size:13px;color:var(--ep-muted)">{{ $summary }}</p>
          @endif
          <div class="footer"><span class="price">Read article</span><span class="btn" style="padding:6px 14px;font-size:12px;">Open</span></div>
        </div>
      </a>
    @empty
      <p class="body-text" style="grid-column:1/-1;text-align:center;padding:48px 0">No published posts yet. Check back soon.</p>
    @endforelse
  </div>

{{ $posts->withQueryString()->onEachSide(1)->links('pagination.ep') }}
@endsection
