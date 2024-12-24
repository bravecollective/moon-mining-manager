@extends('layouts.master-public', ['page' => 'moons'])

@section('title', 'Moons')

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
        <form class="external-filters">
            <!-- Region Filter (Dropdown) -->
            <label for="region-filter">Region:</label>
            <select id="region-filter" class="external-filter search" data-column="1">
                <option value="">All Regions</option>
                @foreach (collect($moons)->pluck('region.regionName')->unique()->sort() as $region)
                    <option value="{{ $region }}">{{ $region }}</option>
                @endforeach
            </select>

            <!-- System Filter (Searchable Dropdown) -->
            <label for="system-filter">System:</label>
            <input type="text" id="system-filter" class="external-filter search" data-column="2" placeholder="Search System">

            <!-- Mineral Name Filter (Searchable Dropdown) -->
            <label for="mineral-filter">Minerals:</label>
            <input type="text" id="mineral-filter" class="external-filter search" data-column="5,6,7,8" placeholder="Try: Tungsten|Caesium">

            <label for="status-filter">Status:</label>
            <select class="external-filter search" data-column="13">
                <option value="">All</option>
                <option value="Available">Available</option>
                <option value="Alliance owned">Alliance owned</option>
                <option value="Lottery only">Lottery only</option>
                <option value="Reserved">Reserved</option>
            </select>

        </form>

        <table id="moons" class="moons">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Region</th>
                    <th>System</th>
                    <th>P</th>
                    <th>M</th>
                    <th>Mineral #1</th>
                    <th>Mineral #2</th>
                    <th>Mineral #3</th>
                    <th>Mineral #4</th>
                    <th>Total %</th>
                    <th class="numeric">Rent (Active)</th>
                    <th class="numeric">Rent (Passive)</th>
                    <th>Available On</th>
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
                          @if ($renter = $moon->getActiveRenterAttribute())
                            {{ ($renter->end_date)? $renter->end_date : date('Y-m-d', strtotime($renter->start_date . ' + 183 days')) }}
                          @endif
                        </td>
                        <td>
                            {{ match($moon->status_flag) {
                                \App\Models\Moon::STATUS_ALLIANCE_OWNED => 'Alliance owned',
                                \App\Models\Moon::STATUS_LOTTERY_ONLY => 'Lottery only',
                                \App\Models\Moon::STATUS_RESERVED => 'Reserved',
                                \App\Models\Moon::STATUS_AVAILABLE => 'Available',
                                default => '',
                            } }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>

    <script>

        window.addEventListener('load', function () {
            var $moonsTable = $('#moons');

            $moonsTable
            .tablesorter({
                widthFixed: true,
                debug: true,
                widgets: ['filter'],
                widgetOptions: {
                    filter_columnFilters: false,
                    filter_filteredRow: 'filtered',
                    filter_external: '.search',
                    // filter_saveFilters: true,
                },
            });
        });

    </script>

@endsection
