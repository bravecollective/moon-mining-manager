<div class="block">

    <div class="card-heading">Total income</div>

    <div class="card highlight">
        <span class="num">{{ number_format($total_income) }}</span> ISK
    </div>

    @foreach ($refineries as $refinery)
        @include('common.card', [
            'link' => '/renters/refinery/' . $refinery->observer_id,
            'size' => 'small',
            'avatar' => 'https://images.evetech.net/types/35835/render?size=128',
            'name' => $refinery->name,
            'amount' => $refinery->income,
            'is_active' => $refinery->extraction_start_time,
        ])
    @endforeach

</div>
