@extends('layouts.master')

@section('title', 'Miners')

@section('content')

    <div class="row">

        <div class="col-12">
            <table id="miners">
                <thead>
                    <tr>
                        @include('miners._sortable-header', ['column' => 'name', 'label' => 'Miner'])
                        @include('miners._sortable-header', ['column' => 'corporation', 'label' => 'Corporation'])
                        @include('miners._sortable-header', ['column' => 'amount_owed', 'label' => 'Amount owed', 'class' => 'numeric'])
                        @include('miners._sortable-header', ['column' => 'total_payments', 'label' => 'Total payments', 'class' => 'numeric'])
                        @include('miners._sortable-header', ['column' => 'latest_mining_activity', 'label' => 'Last mining date'])
                        @include('miners._sortable-header', ['column' => 'latest_invoice', 'label' => 'Last invoice date'])
                        @include('miners._sortable-header', ['column' => 'latest_payment', 'label' => 'Last payment date'])
                    </tr>
                </thead>
                <tbody>
                    @foreach ($miners as $miner)
                        <tr>
                            <td><a href="/miners/{{ $miner->eve_id }}">{{ $miner->name }}</a></td>
                            <td>
                                @if (isset($miner->corporation))
                                    {{ $miner->corporation->name }}
                                @else
                                    UNKNOWN
                                @endif
                            </td>
                            <td class="numeric">{{ number_format($miner->amount_owed, 0) == '-0' ? '0' : number_format($miner->amount_owed, 0) }}</td>
                            <td class="numeric">{{ number_format($miner->total_payments, 0) == '-0' ? '0' : number_format($miner->total_payments, 0) }}</td>
                            <td>{{ date('M j, Y', strtotime($miner->latest_mining_activity)) }}</td>
                            <td>
                                @if (isset($miner->latest_invoice))
                                    {{ date('M j, Y', strtotime($miner->latest_invoice)) }}
                                @endif
                            </td>
                            <td>
                                @if (isset($miner->latest_payment))
                                    {{ date('M j, Y', strtotime($miner->latest_payment)) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $miners->links() }}
        </div>

    </div>

    <script>

        window.addEventListener('load', function () {
            $('#miners tbody tr').on('click', function (event) {
                if ($(event.target).closest('a').length === 0) {
                    $(this).find('a')[0].click();
                }
            });
        });

    </script>

@endsection
