<div class="block">

    <div class="card-heading">Total income</div>

    <div class="card highlight">
        <span class="num">{{ number_format($total_income) }}</span> ISK
    </div>

    @foreach ($refineries as $refinery)
        @include('common.card', [
            'link' => '/renters/refinery/' . $refinery->observer_id,
            'size' => 'small',
            'avatar' => 'https://images.evetech.net/Render/35835_128.png',
            'name' => $refinery->name,
            'amount' => $refinery->income,
            'is_active' => $refinery->extraction_start_time,
        ])
    @endforeach

</div>
