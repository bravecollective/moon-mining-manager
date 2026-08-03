<?php

namespace Tests\Feature;

use App\Http\Controllers\MinerController;
use App\Models\Miner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MinerSortingTest extends TestCase
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

        Schema::create('payments', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('miner_id');
            $table->decimal('amount_received', 17, 2);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('miner_id');
            $table->timestamps();
        });

        Schema::create('mining_activities', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('miner_id');
            $table->timestamps();
        });

        DB::table('corporations')->insert([
            ['corporation_id' => 10, 'name' => 'Beta Corporation'],
            ['corporation_id' => 20, 'name' => 'Gamma Corporation'],
            ['corporation_id' => 30, 'name' => 'Alpha Corporation'],
        ]);

        Miner::insert([
            ['eve_id' => 1, 'corporation_id' => 10, 'name' => 'Alpha Miner', 'amount_owed' => 10],
            ['eve_id' => 2, 'corporation_id' => 20, 'name' => 'Bravo Miner', 'amount_owed' => 20],
            ['eve_id' => 3, 'corporation_id' => 30, 'name' => 'Charlie Miner', 'amount_owed' => 5],
        ]);

        DB::table('payments')->insert([
            ['miner_id' => 1, 'amount_received' => 100, 'created_at' => '2026-01-02', 'updated_at' => '2026-01-02'],
            ['miner_id' => 2, 'amount_received' => 50, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
            ['miner_id' => 3, 'amount_received' => 200, 'created_at' => '2026-01-03', 'updated_at' => '2026-01-03'],
        ]);

        DB::table('invoices')->insert([
            ['miner_id' => 1, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
            ['miner_id' => 2, 'created_at' => '2026-01-03', 'updated_at' => '2026-01-03'],
            ['miner_id' => 3, 'created_at' => '2026-01-02', 'updated_at' => '2026-01-02'],
        ]);

        DB::table('mining_activities')->insert([
            ['miner_id' => 1, 'created_at' => '2026-01-03', 'updated_at' => '2026-01-03'],
            ['miner_id' => 2, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
            ['miner_id' => 3, 'created_at' => '2026-01-02', 'updated_at' => '2026-01-02'],
        ]);
    }

    /**
     * @dataProvider descendingSorts
     */
    public function testItSortsTheCompleteMinerQuery(string $sort, string $expectedFirstMiner): void
    {
        $view = (new MinerController())->showMiners(Request::create('/miners', 'GET', [
            'sort' => $sort,
            'direction' => 'desc',
        ]));

        $miners = $view->getData()['miners'];

        $this->assertSame($expectedFirstMiner, $miners->first()->name);
        $this->assertSame($sort, $view->getData()['sort']);
        $this->assertSame('desc', $view->getData()['direction']);
    }

    public static function descendingSorts(): array
    {
        return [
            'miner name' => ['name', 'Charlie Miner'],
            'corporation' => ['corporation', 'Bravo Miner'],
            'amount owed' => ['amount_owed', 'Bravo Miner'],
            'total payments' => ['total_payments', 'Charlie Miner'],
            'last mining date' => ['latest_mining_activity', 'Alpha Miner'],
            'last invoice date' => ['latest_invoice', 'Bravo Miner'],
            'last payment date' => ['latest_payment', 'Charlie Miner'],
        ];
    }

    public function testItSortsBeforeApplyingPagination(): void
    {
        $additionalMiners = [];

        for ($eveId = 4; $eveId <= 252; $eveId++) {
            $additionalMiners[] = [
                'eve_id' => $eveId,
                'corporation_id' => 10,
                'name' => sprintf('Miner %03d', $eveId),
                'amount_owed' => 0,
            ];
        }

        $additionalMiners[] = [
            'eve_id' => 253,
            'corporation_id' => 10,
            'name' => 'Zulu Miner',
            'amount_owed' => 0,
        ];

        Miner::insert($additionalMiners);
        DB::table('payments')->insert([
            'miner_id' => 253,
            'amount_received' => 10000,
            'created_at' => '2026-01-04',
            'updated_at' => '2026-01-04',
        ]);

        $view = (new MinerController())->showMiners(Request::create('/miners', 'GET', [
            'sort' => 'total_payments',
            'direction' => 'desc',
        ]));

        $miners = $view->getData()['miners'];

        $this->assertSame('Zulu Miner', $miners->first()->name);
        $this->assertSame(253, $miners->total());
        $this->assertSame(2, $miners->lastPage());
    }

    public function testItFallsBackToSafeSortParameters(): void
    {
        $view = (new MinerController())->showMiners(Request::create('/miners', 'GET', [
            'sort' => ['miners.name desc'],
            'direction' => 'sideways',
        ]));

        $miners = $view->getData()['miners'];

        $this->assertSame('Alpha Miner', $miners->first()->name);
        $this->assertSame('name', $view->getData()['sort']);
        $this->assertSame('asc', $view->getData()['direction']);
    }
}
