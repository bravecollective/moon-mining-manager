<?php

namespace Tests\Feature;

use App\Models\MiningActivity;
use App\Models\Refinery;
use App\Models\Region;
use App\Models\SolarSystem;
use App\Models\Type;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function testExtractionWindowsLinkToGoogleCalendar(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('whitelist', function (Blueprint $table): void {
            $table->integer('eve_id');
            $table->boolean('is_admin')->default(false);
        });

        Auth::shouldReceive('user')->andReturn((object) ['eve_id' => 42]);

        $timer = new Refinery();
        $timer->name = 'Test Refinery';
        $timer->chunk_arrival_time = '2030-01-02 03:04:05';
        $timer->natural_decay_time = '2030-01-02 06:07:08';
        $timer->detonation_time = $timer->natural_decay_time;

        $region = new Region();
        $region->regionName = 'Catch';

        $system = new SolarSystem();
        $system->solarSystemName = 'F7C-H0';
        $system->setRelation('region', $region);
        $timer->setRelation('system', $system);

        $html = view('timers', [
            'miner' => null,
            'activity_log' => null,
            'is_whitelisted_user' => false,
            'timers' => new Collection([$timer]),
        ])->render();

        $this->assertStringContainsString(
            'dates=20300102T030405Z%2F20300102T060708Z',
            $html
        );
        $this->assertStringContainsString(
            'text=Moon%20extraction%20window%3A%20F7C-H0%20-%20Test%20Refinery',
            $html
        );
        $this->assertStringContainsString('location=F7C-H0%2C%20Catch', $html);
    }
}
