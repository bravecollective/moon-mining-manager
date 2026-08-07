@extends('layouts.master-public', ['page' => 'timers'])

@section('title', 'Active Extractions')
@section('body-class', $miner ? 'bar' : '')

@section('content')

    @if ($miner)
        <div class="miner-bar">
            <div class="miner-identity">
                <img src="{{ $miner->avatar }}" alt="">
                {{ $miner->name }}
            </div>
            <div class="miner-amount-owed">
                <span class="heading">Current amount owed:</span>
                <span class="numeric">{{ number_format($miner->amount_owed, 0) }}</span> ISK
            </div>
            <div class="miner-total-income">
                <span class="heading">Total payments to date:</span>
                <span class="numeric">{{ number_format($miner->total_payments, 0) }}</span> ISK
            </div>
            @if ($activity_log)
                <div class="miner-activity-log">
                    <span class="heading">Activity record:</span>
                    <a href="#activity-log" id="show-log">View my mining activity log</a>
                </div>
            @endif
        </div>
    @endif

    <h1>Active Alliance Moon Mining Extraction Timers</h1>

    <h1 id="current_time">{{ gmdate('H:i:s, l j F,') }} EVE</h1>

    <table class="timers">
        <thead>
            <tr>
                <th>System</th>
                <th>Refinery name</th>
                <th>Detonation time</th>
                @if ($is_whitelisted_user)
                    <th class="admin">Primary</th>
                    <th class="admin">Secondary</th>
                @endif
            </tr>
        </thead>
        <tbody>
        @foreach ($timers as $timer)
            <tr
                    @if (strtotime($timer->natural_decay_time) < time())
                        class="past"
                    @endif
            >
                <td>
                    <h2>{{ $timer->system->solarSystemName }}</h2>
                    <h3>{{ $timer->system->region->regionName }}</h3>
                    <a href="http://evemaps.dotlan.net/map/{{
                                str_replace(' ', '_', $timer->system->region->regionName) }}/{{
                                $timer->system->solarSystemName }}">
                        View on Dotlan
                    </a>
                </td>
                <td>{{ $timer->name }}</td>
                <td>
                    @if ($timer->claimed_by_primary || $timer->claimed_by_secondary)
                        {{ date('H:i l jS F', strtotime($timer->detonation_time)) }}
                            <br>
                            <a href="http://time.nakamura-labs.com/?#{{ strtotime($timer->chunk_arrival_time) }}"
                               target="_blank">Timezone conversion</a>
                        @else
                            {{ date('H:i l jS F', strtotime($timer->natural_decay_time)) }}
                            <br>
                            <a href="http://time.nakamura-labs.com/?#{{ strtotime($timer->natural_decay_time) }}"
                               target="_blank">Timezone conversion</a>
                        @endif
                    </td>
                    @if ($is_whitelisted_user)
                        <td class="admin">
                            @if ($timer->claimed_by_primary)
                                <img src="{{ $timer->primary->avatar }}" alt="" class="avatar">
                                {{ $timer->primary->name }}
                                @if (strtotime($timer->natural_decay_time) >= time())
                                    <a href="/timers/clear/1/{{ $timer->observer_id }}">Remove</a>
                                @endif
                            @else
                                @if (strtotime($timer->natural_decay_time) >= time())
                                    <form method="post" action="/timers/claim/1/{{ $timer->observer_id }}">
                                        {{ csrf_field() }}
                                        <label for="detonation">
                                            Enter detonation time
                                            ({{ date('H:i', strtotime($timer->chunk_arrival_time)) }} -
                                            {{ date('H:i', strtotime($timer->natural_decay_time)) }})
                                        </label>
                                        <input id="detonation" name="detonation" type="text" size="10">
                                        <button type="submit">Claim detonation</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                        <td class="admin">
                            @if ($timer->claimed_by_secondary)
                                <img src="{{ $timer->secondary->avatar }}" alt="" class="avatar">
                                {{ $timer->secondary->name }}
                                @if (strtotime($timer->natural_decay_time) >= time())
                                    <a href="/timers/clear/2/{{ $timer->observer_id }}">Remove</a>
                                @endif
                            @else
                                @if (strtotime($timer->natural_decay_time) >= time())
                                    <form method="post" action="/timers/claim/2/{{ $timer->observer_id }}">
                                        {{ csrf_field() }}
                                        <label for="detonation">
                                            Enter detonation time
                                            ({{ date('H:i', strtotime($timer->chunk_arrival_time)) }} -
                                            {{ date('H:i', strtotime($timer->natural_decay_time)) }})
                                        </label>
                                        <input id="detonation" name="detonation" type="text" size="10">
                                        <button type="submit">Claim detonation</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($activity_log)
        <div class="mining-activity">
            <h2>
                Your mining activity log
                (<a href="https://wiki.bravecollective.com/member/alliance/industry/moonmining">wiki link</a>)
            </h2>
            <ul id="activity-log">
                @foreach ($activity_log as $event)
                    @include('blocks.mining-activity-log-entry')
                @endforeach
            </ul>
        </div>
    @endif

    <script>
        window.onload = function () {
            setInterval(function () {
                const weekday = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                const monthNames = [
                    "January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"
                ];
                const x = new Date();
                document.getElementById('current_time').innerHTML =
                    pad(x.getUTCHours()) + ':' + pad(x.getUTCMinutes()) + ':' + pad(x.getUTCSeconds()) + ', ' +
                    weekday[x.getUTCDay()] + ' ' + x.getUTCDate() + ' ' + monthNames[x.getUTCMonth()] +
                    ', EVE';
            }, 1000);
        }

        function pad(num) {
            if (num == 0) return '00';
            if (num > 9) return num;
            return '0' + num;
        }
    </script>

@endsection
