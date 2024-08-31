@extends('layouts.master')

@section('title', 'Renters')

@section('content')

    <div class="row">

        <div class="col-12">

            <div class="card-heading">
                @if ($type === 'current')
                    All current renters ({{ count($renters) }})
                    <a href="/renters/new">[Add new]</a>
                @else
                    Expired rental contracts ({{ count($renters) }})
                @endif
            </div>
            
            <table id="renters">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>ID</th>
                        <th>Refinery name</th>
                        <th>Rental contact</th>
                        <th>Rental type</th>
                        <th>Notes</th>
                        <th class="numeric">Monthly fee</th>
                        <th class="numeric">Currently owed</th>
                        <th class="numeric">Start date</th>
                        <th class="numeric">End date</th>
                        <th>Edit</th>
                        <th>Last edited by</th>
                        <th>Last edited at</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($renters as $renter)
                        <tr>
                            <td>
                                @if (isset($renter->moon_id))
                                    {{ $renter->moon->system->solarSystemName }} -
                                    Planet {{ $renter->moon->planet }},
                                    Moon {{ $renter->moon->moon }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $renter ? $renter->moon_id : '' }}</td>
                            <td>
                                @if (isset($renter->refinery_id))
                                    <a href="/renters/refinery/{{ $renter->refinery_id }}">
                                        {{ $renter->refinery->name }}
                                        ({{ $renter->refinery->system->solarSystemName }})
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                <a href="/renters/character/{{ $renter->character_id }}">
                                    {{ $renter->character_name ? $renter->character_name : '[missing name]' }}
                                </a>
                            </td>
                            <td>{{ ($renter->type === 'individual')? 'active' : 'passive' }}</td>
                            <td>{{ $renter->notes }}</td>
                            <td class="numeric">{{ number_format($renter->monthly_rental_fee, 0) }}</td>
                            <td class="numeric">{{ number_format($renter->amount_owed, 0) }}</td>
                            <td class="numeric">{{ date('M j, Y', strtotime($renter->start_date)) }}</td>
                            <td class="numeric">
                                @if (isset($renter->end_date))
                                    {{ date('M j, Y', strtotime($renter->end_date)) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td><a href="/renters/{{ $renter->id }}">Edit details</a></td>
                            <td>
                                {{ $renter->updatedBy ? $renter->updatedBy->name : '' }}
                            </td>
                            <td>
                                {{ $renter->updated_at }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>

    </div>

    <script>
        window.addEventListener('load', function () {
            $('#renters').tablesorter();
        });
    </script>

@endsection
