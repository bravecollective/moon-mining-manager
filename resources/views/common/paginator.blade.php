@if ($paginator->hasPages())
    @php
        // A five page window centred on the current page and clamped to the ends, rather than the
        // ten or so links the default window renders. The first and last page stay one click away
        // through the ellipses either side. $elements is deliberately unused.
        $window = 5;
        $start = max(1, min($paginator->currentPage() - intdiv($window, 2), $paginator->lastPage() - $window + 1));
        $end = min($paginator->lastPage(), $start + $window - 1);
    @endphp

    <nav>
        <ul class="pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- First Page, when the window has moved off it --}}
            @if ($start > 1)
                <li><a href="{{ $paginator->url(1) }}">1</a></li>
                @if ($start > 2)
                    <li class="disabled" aria-hidden="true"><span>&hellip;</span></li>
                @endif
            @endif

            {{-- The Window --}}
            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $paginator->currentPage())
                    <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                @else
                    <li><a href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                @endif
            @endfor

            {{-- Last Page, when the window stops short of it --}}
            @if ($end < $paginator->lastPage())
                @if ($end < $paginator->lastPage() - 1)
                    <li class="disabled" aria-hidden="true"><span>&hellip;</span></li>
                @endif
                <li><a href="{{ $paginator->url($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a></li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
