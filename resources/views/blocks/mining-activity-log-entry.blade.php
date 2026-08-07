<li>
    {{ date('Y-m-d H:i', strtotime($event->updated_at)) }} -
    @if (isset($event->amount))
        Invoice sent for {{ number_format($event->amount) }} ISK
    @endif
    @if (isset($event->quantity))
        Mining recorded in {{ $event->refinery->system->solarSystemName }}:
        {{ $event->type?->typeName ?? 'Unknown ore' }}
        ({{ number_format($event->quantity, 0) }} units)
        @if (isset($event->tax_amount))
            ~ {{ number_format($event->tax_amount) }} ISK
        @endif
    @endif
    @if (isset($event->amount_received))
        Payment received for {{ number_format($event->amount_received) }} ISK
    @endif
</li>
