@extends('layouts.master')

@section('title', 'Miners')

@section('content')

    <div class="row">

        <div class="col-12">
            <table id="miners">
                <thead>
                    <th>Miner</th>
                    <th>Corporation</th>
                    <th class="numeric">Amount owed</th>
                    <th class="numeric">Total payments</th>
                    <th>Last mining date</th>
                    <th>Last invoice date</th>
                    <th>Last payment date</th>
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
            $('#miners').tablesorter();
            $('#miners tr').on('click', function () {
                $(this).find('a')[0].click();
            });
        });

    </script>

@endsection
