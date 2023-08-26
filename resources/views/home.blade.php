@extends('layouts.master')

@section('title', 'Dashboard')

@section('content')

    <div class="row">

        @if ($top_miner)
            <div class="col-4">
                <div class="card-heading">Top miner</div>
                @include('common.card', [
                    'link' => '/miners/' . $top_miner->eve_id,
                    'avatar' => $top_miner->avatar,
                    'name' => $top_miner->name,
                    'sub' => $top_miner->corporation->name,
                    'amount' => $top_miner->total
                ])
            </div>
        @endif

        @if ($top_refinery)
            <div class="col-4">
                <div class="card-heading">Top Refinery</div>
                @include('common.card', [
                    'avatar' => 'https://images.evetech.net/types/35835/render?size=128',
                    'name' => $top_refinery->name,
                    'sub' => $top_refinery->system ? $top_refinery->system->solarSystemName : null,
                    'amount' => $top_refinery->income
                ])
            </div>
        @endif

        @if ($top_system)
            <div class="col-4">
                <div class="card-heading">Top System</div>
                @include('common.card', [
                    'avatar' => 'https://images.evetech.net/types/3802/render?size=64',
                    'name' => $top_system->solarSystemName,
                    'sub' => $top_system->region->regionName,
                    'amount' => $top_system->total
                ])
            </div>
        @endif

    </div>

    <div class="row">

        <div class="col-6">
            @include('blocks.debtors')
        </div>

        <div class="col-6">
            @include('blocks.refineries')
            @include('blocks.ninjas')
        </div>

    </div>

@endsection
