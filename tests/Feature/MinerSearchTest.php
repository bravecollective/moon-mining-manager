<?php

namespace Tests\Feature;

use App\Http\Controllers\MinerController;
use App\Models\Miner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MinerSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('corporations', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('corporation_id')->unique();
            $table->string('name');
        });

        Schema::create('miners', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('eve_id')->unique();
            $table->integer('corporation_id');
            $table->string('name');
            $table->string('avatar')->default('');
            $table->decimal('amount_owed', 17, 2)->default(0);
            $table->timestamps();
        });

        DB::table('corporations')->insert([
            'corporation_id' => 10,
            'name' => 'Test Corporation',
        ]);

        Miner::insert([
            ['eve_id' => 1001, 'corporation_id' => 10, 'name' => 'Alpha Miner'],
            ['eve_id' => 1002, 'corporation_id' => 10, 'name' => 'Bravo Miner'],
            ['eve_id' => 1003, 'corporation_id' => 10, 'name' => 'Another Alpha'],
        ]);
    }

    public function testItSearchesMinersByPartialName(): void
    {
        $view = $this->showMiners(['search' => 'Alpha']);

        $this->assertSame(
            ['Alpha Miner', 'Another Alpha'],
            $view->getData()['miners']->pluck('name')->all()
        );
        $this->assertSame('Alpha', $view->getData()['search']);
    }

    public function testItSearchesMinerNamesCaseInsensitively(): void
    {
        $view = $this->showMiners(['search' => 'alpha']);

        $this->assertSame(
            ['Alpha Miner', 'Another Alpha'],
            $view->getData()['miners']->pluck('name')->all()
        );
    }

    public function testItSearchesMinersByExactCharacterId(): void
    {
        $view = $this->showMiners(['search' => '1002']);

        $this->assertSame(
            ['Bravo Miner'],
            $view->getData()['miners']->pluck('name')->all()
        );
    }

    public function testItPreservesSearchWhenPaginating(): void
    {
        $view = $this->showMiners(['search' => 'Miner']);

        $this->assertStringContainsString('search=Miner', $view->getData()['miners']->url(2));
    }

    private function showMiners(array $query)
    {
        $request = Request::create('/miners', 'GET', $query);
        $this->app->instance('request', $request);

        return (new MinerController())->showMiners($request);
    }
}
