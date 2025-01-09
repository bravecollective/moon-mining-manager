@extends('layouts.master')

@php
    use Faker\Factory as Faker;

    $faker = Faker::create();
    $moons = collect(range(1, 600))->map(function ($id) use ($faker) {
        $regions = ['Delve', 'Querious'];
        $systems = [
            '1DQ1-A', 'T5ZI-S', 'M2-XFE', 'C3N-3S', '5BTK-M',
            'D-W7F0', '8QT-H4', 'SVM-3K', 'V-IH6B', 'U9U-TQ'
        ];
        $moonGooMinerals = [
            'Chromium', 'Cobalt', 'Scandium', 'Titanium',
            'Tungsten', 'Vanadium', 'Cadmium', 'Platinum'
        ];

		$mineral2 = $faker->boolean;
		$mineral3 = $faker->boolean;
		$mineral4 = $faker->boolean;

        $data = [
            'id' => $id,
            'region' => (object)['regionName' => $faker->randomElement($regions)],
            'system' => (object)['solarSystemName' => $faker->randomElement($systems)],
            'planet' => $faker->numberBetween(1, 10),
            'moon' => $faker->numberBetween(1, 5),
            'mineral_1' => (object)['typeName' => $faker->randomElement($moonGooMinerals)],
            'mineral_1_percent' => $faker->randomFloat(2, 5, 50),
            'mineral_2_type_id' => $mineral2 ? $faker->randomNumber() : 0,
            'mineral_2' => $mineral2 ? (object)['typeName' => $faker->randomElement($moonGooMinerals)] : (object)['typeName' => '' ],
            'mineral_2_percent' => $mineral2 ? $faker->randomFloat(2, 5, 50) : 0,
            'mineral_3_type_id' => $mineral3 ? $faker->randomNumber() : 0,
            'mineral_3' => $mineral3 ? (object)['typeName' => $faker->randomElement($moonGooMinerals)] : (object)['typeName' => '' ],
            'mineral_3_percent' => $mineral3 ? $faker->randomFloat(2, 5, 50) : 0,
            'mineral_4_type_id' => $mineral4 ? $faker->randomNumber() : 0,
            'mineral_4' => $mineral4 ? (object)['typeName' => $faker->randomElement($moonGooMinerals)] : (object)['typeName' => '' ],
            'mineral_4_percent' => $mineral4 ? $faker->randomFloat(2, 5, 50) : 0,
            'monthly_rental_fee' => $faker->numberBetween(5000000, 20000000),
            'monthly_corp_rental_fee' => $faker->numberBetween(1000000, 5000000),
            'previous_monthly_rental_fee' => $faker->numberBetween(5000000, 20000000),
            'previous_monthly_corp_rental_fee' => $faker->numberBetween(1000000, 5000000),
            'active_renter' => $faker->boolean ? (object)[
                'character_name' => $faker->name,
                'type' => $faker->randomElement(['individual', 'corporate']),
                'end_date' => $faker->date(),
                'start_date' => $faker->date(),
            ] : null,
            'status_flag' => $faker->randomElement([
                \App\Models\Moon::STATUS_AVAILABLE,
                \App\Models\Moon::STATUS_ALLIANCE_OWNED,
                \App\Models\Moon::STATUS_LOTTERY_ONLY,
                \App\Models\Moon::STATUS_RESERVED,
            ]),
            'updated_at' => $faker->dateTimeThisYear->format('Y-m-d H:i:s'),
        ];

        return (object)$data;
    });

@endphp

@section('title', 'Moon Composition Data')

@section('content')

    <div class="row" id="moonAdminList" data-csrf-token="{{ csrf_token() }}">
        <div class="col-12">
            <div class="card-heading">Existing Moon Data</div>
            <form class="external-filters">
                <!-- Region Filter (Dropdown) -->
                <label for="region-filter">Region:</label>
                <select id="region-filter" class="external-filter search" data-column="1">
                    <option value="">All Regions</option>
                    @foreach (collect($moons)->pluck('region.regionName')->unique()->sort() as $region)
                        <option value="{{ $region }}">{{ $region }}</option>
                    @endforeach
                </select>

                <label for="system-filter">System:</label>
                <input type="text" id="system-filter" class="external-filter search" data-column="2" placeholder="Search System">

                <label for="system-filter">Renter:</label>
                <input type="text" id="system-filter" class="external-filter search" data-column="9" placeholder="Search Renters">

                <label for="mineral-filter">Minerals:</label>
                <input type="text" id="mineral-filter" class="external-filter search" data-column="5" placeholder="Try: Cadmium|Cobalt">

                <label for="status-filter">Status:</label>
                <select class="external-filter search" data-column="11">
                    <option value="">All</option>
                    <option value="Available">Available</option>
                    <option value="Alliance owned">Alliance owned</option>
                    <option value="Lottery only">Lottery only</option>
                    <option value="Reserved">Reserved</option>
                </select>

            </form>
            <table id="moons">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Region</th>
                        <th>System</th>
                        <th>P</th>
                        <th>M</th>
                        <th>Mineral composition</th>
                        <th>Total %</th>
                        <th class="numeric">
                            Active fee<br>
                            Passive fee
                        </th>
                        <th class="numeric">Last month</th>
                        <th>Renter</th>
                        <th>Type</th>
                        <th class="moon-status-head">Status</th>
                        <th>updated</th>
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
                            <td>{{ $moon->system->solarSystemName }}</td>
                            <td>{{ $moon->planet }}</td>
                            <td>{{ $moon->moon }}</td>
                            <td>
                                {{ $moon->mineral_1?->typeName }} ({{ round($moon->mineral_1_percent, 2) }}%)
                                @if ($moon->mineral_2_type_id)
                                    &#0183; {{ $moon->mineral_2->typeName }} ({{ round($moon->mineral_2_percent, 2) }}%)
                                @endif
                                @if ($moon->mineral_3_type_id)
                                    &#0183; {{ $moon->mineral_3->typeName }} ({{ round($moon->mineral_3_percent, 2) }}%)
                                @endif
                                @if ($moon->mineral_4_type_id)
                                    &#0183; {{ $moon->mineral_4->typeName }} ({{ round($moon->mineral_4_percent, 2) }}%)
                                @endif
                            </td>
                            <td>
                                {{ round(
                                    $moon->mineral_1_percent + $moon->mineral_2_percent +
                                        $moon->mineral_3_percent + $moon->mineral_4_percent,
                                    2
                                ) }}%
                            </td>
                            <td class="numeric">
                                {{ number_format($moon->monthly_rental_fee) }}<br>
                                {{ number_format($moon->monthly_corp_rental_fee) }}
                            </td>
                            <td class="numeric">
                                {{ number_format($moon->previous_monthly_rental_fee) }}<br>
                                {{ number_format($moon->previous_monthly_corp_rental_fee) }}
                            </td>
                            <td>
                                {{ $moon->active_renter ? $moon->active_renter->character_name : '' }}
                            </td>
                            <td>
                                {{ $moon->active_renter ? ($moon->active_renter->type === 'individual')? 'active' : 'passive' : '' }}
                            </td>
                            <td class="moon-status"
                                data-moon-id="{{ $moon->id }}"
                                data-old-value="{{ $moon->status_flag }}"
                            >
                                <span class="moonStatusText"></span>
                                <span class="moonStatusSelect"></span>
                                <!--suppress JSUnresolvedFunction -->
                                <small style="cursor: pointer; text-decoration: underline dotted grey"
                                       onclick="showStatusSelect(this)"
                                >edit</small>
                            </td>
                            <td>{{ $moon->updated_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script type="text/javascript">
        window.addEventListener('load', function() {
            $('#moons th.moon-status-head').on('click', function () {
                // trigger an update to sort changed values
                $('#moons').trigger('update');
            });

            $('.moon-status').each(function () {
                setStatusText($(this));
            });

            $('#moons').tablesorter({
                widthFixed: true,
                widgets: ['filter'],
                widgetOptions: {
                    filter_columnFilters: false,
                    filter_filteredRow: 'filtered',
                    filter_external: '.search',
                },
            });
        });

        function showStatusSelect(editTextElement) {
            const $moonStatus = $(editTextElement).parent();
            const $selectWrap = $moonStatus.find('.moonStatusSelect');

            const $select = $('<select/>');
            $select.append('<option value="0">Available</option>');
            $select.append('<option value="1">Alliance owned</option>');
            $select.append('<option value="2">Lottery only</option>');
            $select.append('<option value="3">Reserved</option>');
            $select.val($moonStatus.data('oldValue'));

            $selectWrap.append($select);
            $moonStatus.find('.moonStatusText').hide();

            $select.on('change', function () {
                updateMoonStatus($select.val(), $moonStatus);
                $selectWrap.empty();
            });
        }

        function updateMoonStatus(newValue, $moonStatus) {
            const moonId = $moonStatus.data('moonId');
            $.post('/moon-admin/update-status', {
                _token: document.getElementById('moonAdminList').dataset.csrfToken,
                id: moonId,
                status: newValue,
            }, function(data) {
                const $sysMessage = $('#systemMessage');
                if (data && data.success) {
                    $sysMessage.text('Success.');
                    $moonStatus.data('oldValue', newValue);
                    setStatusText($moonStatus, newValue);
                } else {
                    $sysMessage.text('Error!');
                }
                $sysMessage.show();
                window.setTimeout(function () {
                    $sysMessage.hide();
                }, 2000);
                $moonStatus.find('.moonStatusText').show();
            });
        }

        /**
         * @param $moonStatus
         * @param [statusFlag]
         */
        function setStatusText($moonStatus, statusFlag) {
            if (statusFlag) {
                statusFlag = parseInt(statusFlag, 10);
            } else {
                statusFlag = $moonStatus.data('oldValue');
            }
            const $textWrap = $moonStatus.find('.moonStatusText');
            if (statusFlag === 0) {
                $textWrap.text('Available');
            } else if (statusFlag === 1) {
                $textWrap.text('Alliance owned');
            } else if (statusFlag === 2) {
                $textWrap.text('Lottery only');
            } else if (statusFlag === 3) {
                $textWrap.text('Reserved');
            }
        }
    </script>

@endsection
