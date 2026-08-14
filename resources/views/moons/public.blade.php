@extends('layouts.master-public', ['page' => 'moons'])

@section('title', 'Moons')

@php
    /** Link for a sortable column heading, carrying the current filters and flipping direction. */
    $sortUrl = function (string $key) use ($filters) {
        $descending = $filters['sort'] === $key && $filters['dir'] === 'asc';

        return url()->current().'?'.http_build_query(array_merge(
            request()->except(['sort', 'dir', 'page']),
            ['sort' => $key, 'dir' => $descending ? 'desc' : 'asc'],
        ));
    };

    $sortArrow = fn (string $key) => ($filters['sort'] === $key)
        ? (($filters['dir'] === 'asc') ? ' ▲' : ' ▼')
        : '';
@endphp

@section('content')

    <h1>Alliance Moons</h1>

    <p class="center">
        To inquire about renting a moon, please use the
        <!--suppress HtmlUnknownTarget -->
        <a href="/contact-form">contact form</a>
        quoting the relevant moon ID.
    </p>
    <p class="center">
        For more information on the Brave moon rental program, please consult
        <a href="https://wiki.bravecollective.com/member/alliance/industry/moon-rental" target="_blank">this wiki page</a>.
    </p>
    <br>
    <p class="center">
        Click on table headings to sort.
    </p>

    <div class="row">
        <div class="table-nav">
            <form class="external-filters" method="GET" action="{{ url('/moons') }}">
                <!-- Region Filter (Dropdown) -->
                <div class="filter-field">
                    <label for="region-filter">Region</label>
                    <select name="region" id="region-filter">
                        <option value="">All Regions</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->region_id }}" @selected($filters['region'] === (int) $region->region_id)>
                                {{ $region->regionName }} ({{ number_format($region->moon_count) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- System Filter (name prefix) -->
                <div class="filter-field">
                    <label for="system-filter">System</label>
                    <input type="text" name="system" id="system-filter" value="{{ $filters['system'] }}" placeholder="Search System">
                </div>

                <!-- Mineral Filter (checkboxes, in a panel that opens on hover or focus) -->
                <div class="filter-field mineral-filter">
                    <span class="filter-label">Minerals</span>

                    <button type="button" class="mineral-toggle" aria-haspopup="true">
                        @if ($filters['mineral'])
                            {{ count($filters['mineral']) }} selected
                        @else
                            Any mineral
                        @endif
                    </button>

                    <div class="mineral-panel">
                        @foreach ($minerals as $type)
                            <label>
                                <input
                                    type="checkbox"
                                    name="mineral[]"
                                    value="{{ $type->typeID }}"
                                    @checked(in_array((int) $type->typeID, $filters['mineral'], true))
                                >
                                {{ $type->typeName }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="filter-field">
                    <label for="status-filter">Status</label>
                    <select name="status" id="status-filter">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit">Filter</button>
                    <a class="reset" href="{{ url('/moons') }}">Reset</a>
                </div>
            </form>

            @unless ($moons->isEmpty())
                <div class="pagination-nav">
                    <span class="count">
                        {{ number_format($moons->firstItem()) }}&ndash;{{ number_format($moons->lastItem()) }}
                        of {{ number_format($moons->total()) }} moons
                    </span>
                    {{ $moons->links() }}
                </div>
            @endunless
        </div>

        @if ($moons->isEmpty())

            <p class="center">
                No moons match these filters.
                <a href="{{ url('/moons') }}">Reset</a>
            </p>

        @else

            <table id="moons" class="moons">
                <thead>
                    <tr>
                        <th><a href="{{ $sortUrl('id') }}">ID{{ $sortArrow('id') }}</a></th>
                        <th><a href="{{ $sortUrl('region') }}">Region{{ $sortArrow('region') }}</a></th>
                        <th><a href="{{ $sortUrl('system') }}">System{{ $sortArrow('system') }}</a></th>
                        <th><a href="{{ $sortUrl('planet') }}">P{{ $sortArrow('planet') }}</a></th>
                        <th>M</th>
                        <th>Mineral #1</th>
                        <th>Mineral #2</th>
                        <th>Mineral #3</th>
                        <th>Mineral #4</th>
                        <th><a href="{{ $sortUrl('total') }}">Total %{{ $sortArrow('total') }}</a></th>
                        <th class="numeric"><a href="{{ $sortUrl('active') }}">Rent (Active){{ $sortArrow('active') }}</a></th>
                        <th class="numeric"><a href="{{ $sortUrl('passive') }}">Rent (Passive){{ $sortArrow('passive') }}</a></th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($moons as $moon)
                        <tr
                            @if (isset($moon->active_renter) || $moon->status_flag != \App\Models\Moon::STATUS_AVAILABLE)
                                class="rented"
                            @endif
                        >
                            <td>{{ $moon->id }}</td>
                            <td>{{ $moon->region->regionName }}</td>
                            <td class="nobreak">{{ $moon->system->solarSystemName }}</td>
                            <td>{{ $moon->planet }}</td>
                            <td>{{ $moon->moon }}</td>
                            <td>{{ $moon->mineral_1?->typeName }} ({{ round($moon->mineral_1_percent, 2) }}%)</td>
                            <td>
                                @if ($moon->mineral_2_type_id)
                                    {{ $moon->mineral_2?->typeName }} ({{ round($moon->mineral_2_percent, 2) }}%)
                                @endif
                            </td>
                            <td>
                                @if ($moon->mineral_3_type_id)
                                    {{ $moon->mineral_3->typeName }} ({{ round($moon->mineral_3_percent, 2) }}%)
                                @endif
                            </td>
                            <td>
                                @if ($moon->mineral_4_type_id)
                                    {{ $moon->mineral_4->typeName }} ({{ round($moon->mineral_4_percent,2 ) }}%)
                                @endif
                            </td>
                            <td>
                                {{ round(
                                    $moon->mineral_1_percent + $moon->mineral_2_percent +
                                        $moon->mineral_3_percent + $moon->mineral_4_percent,
                                    2
                                ) }}%
                            </td>
                            <td class="numeric">{{ number_format($moon->monthly_rental_fee) }}</td>
                            <td class="numeric">{{ number_format($moon->monthly_corp_rental_fee) }}</td>
                            <td>
                                {{ match($moon->status_flag) {
                                    \App\Models\Moon::STATUS_ALLIANCE_OWNED => 'Alliance owned',
                                    \App\Models\Moon::STATUS_LOTTERY_ONLY => 'Lottery only',
                                    \App\Models\Moon::STATUS_RESERVED => 'Reserved',
                                    \App\Models\Moon::STATUS_AVAILABLE => empty( $moon->active_renter ) ? 'Available' : 'Rented',
                                    default => '',
                                } }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="table-nav">
                <div class="pagination-nav">
                    <span class="count">
                        {{ number_format($moons->firstItem()) }}&ndash;{{ number_format($moons->lastItem()) }}
                        of {{ number_format($moons->total()) }} moons
                    </span>
                    {{ $moons->links() }}
                </div>
            </div>

        @endif

    </div>

@endsection
