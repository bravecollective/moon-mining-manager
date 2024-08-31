@extends('layouts.master')

@section('title', 'Moon Composition Data')

@section('content')

    <div class="row" id="moonAdminList" data-csrf-token="{{ csrf_token() }}">
        <div class="col-12">
            <div class="card-heading">Existing Moon Data</div>
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
            $('#moons').tablesorter();
            $('#moons th.moon-status-head').on('click', function () {
                // trigger an update to sort changed values
                $('#moons').trigger('update');
            });

            $('.moon-status').each(function () {
                setStatusText($(this));
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
