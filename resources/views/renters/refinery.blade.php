@extends('layouts.master')

@section('title', 'Refinery Details')

@section('content')

    <div class="row">

        <div class="col-4">
            <div class="card-heading">Refinery</div>
            @include('common.card', [
                'avatar' => 'https://images.evetech.net/Render/35835_128.png',
                'name' => $refinery->name,
                'sub' => $renter ?
                    'P' . $renter->moon->planet . '-M' .
                        $renter->moon->moon . ', ' .
                        $renter->refinery->system->solarSystemName . ', ' .
                        $renter->moon->region->regionName . ', ID: ' . $renter->moon_id :
                    $refinery->name,
                'sub2' => $refinery->corporation ? $refinery->corporation->name : ''
            ])
        </div>

        <div class="col-4">
            <div class="card-heading">Rented by</div>
            @if($renter)
                @include('common.card', [
                    'link' => '/renters/character/' . $renter->character_id,
                    'avatar' => $renter->character->avatar->px128x128,
                    'name' => $renter->character->name,
                    'sub' => $renter->character->corporation->name
                ])
            @else
                @include('common.card', [
                    'avatar' => '',
                    'name' => 'Not rented.'
                ])
            @endif
        </div>

        <div class="col-4">
            <div class="card-heading">Monthly rent</div>
            <div class="card highlight">
                @if($renter)
                    <span class="num">{{ number_format($renter->monthly_rental_fee) }}</span> ISK
                @endif
            </div>
        </div>

    </div>

    <div class="row">

        <div class="col-8">

            <div class="card-heading">Activity Log (<a href="/payment/new">Payment received</a>)</div>

            <table>
                <thead>
                    <tr>
                        <th>Activity</th>
                        <th class="numeric">Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activity_log as $activity)
                        <tr>
                            <td>
                                @if (isset($activity->amount))
                                    Invoice sent
                                @endif
                                @if (isset($activity->amount_received))
                                    Payment received
                                @endif
                            </td>
                            <td class="numeric">
                                @if (isset($activity->amount))
                                    {{ number_format($activity->amount) }} ISK
                                @endif
                                @if (isset($activity->amount_received))
                                    {{ number_format($activity->amount_received) }} ISK
                                @endif
                            </td>
                            <td>
                                {{ date('g:ia, jS F Y', strtotime($activity->created_at)) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>

        <div class="col-4">
            <div class="card-heading">Last edited</div>
            <div class="card card-regular">
                by: {{ $renter && $renter->updatedBy ? $renter->updatedBy->name : '' }}
                <br>
                at: {{ $renter ? $renter->updated_at : '' }}
            </div>
        </div>

    </div>

@endsection
