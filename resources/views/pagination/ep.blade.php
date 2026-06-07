@if ($paginator->hasPages())
  <nav class="pagination" aria-label="Pagination">
    @if ($paginator->onFirstPage())
      <span>‹ Prev</span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Prev</a>
    @endif

    @foreach ($elements as $element)
      @if (is_string($element))
        <span>{{ $element }}</span>
      @endif

      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <span class="current">{{ $page }}</span>
          @else
            <a href="{{ $url }}">{{ $page }}</a>
          @endif
        @endforeach
      @endif
    @endforeach

    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a>
    @else
      <span>Next ›</span>
    @endif
  </nav>
@endif
