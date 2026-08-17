@extends('layouts.master')

@section('title', 'Miners')

@section('content')

    <div class="row">

        <div class="col-12">
            <form method="get" action="/miners" class="external-filters">
                <label for="miner-search">Search miners</label>
                <input
                    type="search"
                    id="miner-search"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Name or character ID"
                >
                <button type="submit">Search</button>
                @if ($search !== '')
                    <a href="/miners">Clear</a>
                @endif
            </form>
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
                    @forelse ($miners as $miner)
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
                    @empty
                        <tr>
                            <td colspan="7">No miners match your search.</td>
                        </tr>
                    @endforelse
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
