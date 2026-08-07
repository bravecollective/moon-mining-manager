<?php

namespace Tests\Feature;

use App\Models\MiningActivity;
use App\Models\Refinery;
use App\Models\SolarSystem;
use App\Models\Type;
use Tests\TestCase;

class MiningActivityLogTest extends TestCase
{
    public function testMiningEntriesShowTheirOreType(): void
    {
        $activity = new MiningActivity();
        $activity->quantity = 1250;
        $activity->updated_at = '2026-08-07 12:30:00';

        $type = new Type();
        $type->typeName = 'Compressed Veldspar';
        $activity->setRelation('type', $type);

        $refinery = new Refinery();
        $system = new SolarSystem();
        $system->solarSystemName = 'F7C-H0';
        $refinery->setRelation('system', $system);
        $activity->setRelation('refinery', $refinery);

        $html = view('blocks.mining-activity-log-entry', ['event' => $activity])->render();

        $this->assertStringContainsString('Mining recorded in F7C-H0:', $html);
        $this->assertStringContainsString('Compressed Veldspar', $html);
        $this->assertStringContainsString('1,250 units', $html);
    }
}
