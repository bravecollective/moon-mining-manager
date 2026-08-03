@php
    $isActive = $sort === $column;
    $nextDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
    $query = array_merge(request()->except(['page', 'sort', 'direction']), [
        'sort' => $column,
        'direction' => $nextDirection,
    ]);
@endphp

<th class="sortable-header {{ $class ?? '' }}" @if ($isActive) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <a href="{{ request()->url() }}?{{ http_build_query($query) }}">
        {{ $label }}
        @if ($isActive)
            <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
            <span class="sr-only">, sorted {{ $direction === 'asc' ? 'ascending' : 'descending' }}</span>
        @endif
    </a>
</th>
