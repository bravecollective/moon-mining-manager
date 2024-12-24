@extends('layouts.master-public', ['page' => 'moons'])

@section('title', 'Moons')

@php
    $faker = Faker\Factory::create();
	class FakeMoon {
        public $id;
        public $region;
        public $system;
        public $planet;
        public $moon;
        public $mineral_1;
        public $mineral_1_percent;
        public $mineral_2_type_id;
        public $mineral_2;
        public $mineral_2_percent;
        public $mineral_3_type_id;
        public $mineral_3;
        public $mineral_3_percent;
        public $mineral_4_type_id;
        public $mineral_4;
        public $mineral_4_percent;
        public $monthly_rental_fee;
        public $monthly_corp_rental_fee;
        public $active_renter;
        public $status_flag;

        public function __construct($data) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }

        public function getActiveRenterAttribute() {
            return false;
        }
    }

    $regions = ['Delve', 'Querious']; // Nullsec regions

    $systems = [ // Example nullsec systems from Delve and Querious
        '1-SMEB', '5-CQDA', '319-3D', 'NOL-M9', 'QX-LIJ',
        '8QT-H4', 'D-W7F0', '9GNS-2', '31X-RE', 'Z-PNIA'
    ];

    $moonGooMinerals = [ // Common Moon Goo minerals from EVE Online
        'Atmospheric Gases', 'Evaporite Deposits', 'Hydrocarbons', 'Silicates',
        'Cobalt', 'Scandium', 'Titanium', 'Tungsten',
        'Chromium', 'Platinum', 'Vanadium', 'Cadmium',
        'Caesium', 'Hafnium', 'Mercury', 'Technetium',
        'Dysprosium', 'Neodymium', 'Promethium', 'Thulium'
    ];

    $moons = collect(range(1, 5000))->map(function ($id) use ($faker, $regions, $systems, $moonGooMinerals) {
        $data = [
            'id' => $id,
            'region' => (object)['regionName' => $faker->randomElement($regions)], // Only two regions
            'system' => (object)['solarSystemName' => $faker->randomElement($systems) ],
            'planet' => $faker->numberBetween(1, 10),
            'moon' => $faker->numberBetween(1, 5),
            'mineral_1' => (object)['typeName' => $faker->randomElement($moonGooMinerals)], // Choose from the defined minerals
            'mineral_1_percent' => $faker->randomFloat(2, 5, 50),
            'mineral_2_type_id' => $faker->boolean ? $faker->randomNumber() : 0,
            'mineral_2' => $faker->boolean ? (object)['typeName' => $faker->randomElement($moonGooMinerals)] : (object)['typeName' => $faker->randomElement($moonGooMinerals)],
            'mineral_2_percent' => $faker->boolean ? $faker->randomFloat(2, 5, 50) : 0,
            'mineral_3_type_id' => $faker->boolean ? $faker->randomNumber() : 0,
            'mineral_3' => $faker->boolean ? (object)['typeName' => $faker->randomElement($moonGooMinerals)] : (object)['typeName' => $faker->randomElement($moonGooMinerals)],
            'mineral_3_percent' => $faker->boolean ? $faker->randomFloat(2, 5, 50) : 0,
            'mineral_4_type_id' => $faker->boolean ? $faker->randomNumber() : 0,
            'mineral_4' => $faker->boolean ? (object)['typeName' => $faker->randomElement($moonGooMinerals)] : (object)['typeName' => $faker->randomElement($moonGooMinerals)],
            'mineral_4_percent' => $faker->boolean ? $faker->randomFloat(2, 5, 50) : 0,
            'monthly_rental_fee' => $faker->numberBetween(5000000, 20000000),
            'monthly_corp_rental_fee' => $faker->numberBetween(1000000, 5000000),
            'active_renter' => $faker->boolean ? (object)[
                'end_date' => $faker->date(),
                'start_date' => $faker->date()
            ] : null,
            'status_flag' => $faker->randomElement( [
				0,
                \App\Models\Moon::STATUS_ALLIANCE_OWNED,
                \App\Models\Moon::STATUS_LOTTERY_ONLY,
                \App\Models\Moon::STATUS_RESERVED,
            ] ),
        ];

        return new FakeMoon($data);
    });



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
