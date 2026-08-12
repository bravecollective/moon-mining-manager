<?php

namespace Tests\Unit;

use App\Jobs\GenerateInvoice;
use App\Jobs\GenerateInvoices;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionProperty;
use Tests\TestCase;

class GenerateInvoicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('miners', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('eve_id')->unique();
            $table->integer('corporation_id');
            $table->integer('alliance_id')->nullable();
            $table->string('name');
            $table->string('avatar');
            $table->decimal('amount_owed', 17, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('mining_activities', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('miner_id');
            $table->timestamps();
        });

        config()->set('eve.alliances_whitelist', '1');
        config()->set('eve.corporations_whitelist');
        Bus::fake();
    }

    public function test_recently_active_miners_are_invoiced_first(): void
    {
        DB::table('miners')->insert([
            $this->miner(1001, '2026-08-01 00:00:00'),
            $this->miner(1002, '2026-08-10 00:00:00'),
            $this->miner(1003, '2026-07-01 00:00:00'),
        ]);

        DB::table('mining_activities')->insert([
            [
                'miner_id' => 1001,
                'created_at' => '2026-08-05 00:00:00',
                'updated_at' => '2026-08-05 00:00:00',
            ],
            [
                'miner_id' => 1003,
                'created_at' => '2026-08-11 00:00:00',
                'updated_at' => '2026-08-11 00:00:00',
            ],
        ]);

        (new GenerateInvoices)->handle();

        $ids = Bus::dispatched(GenerateInvoice::class)
            ->map(fn (GenerateInvoice $job) => $this->jobMinerId($job))
            ->all();

        $this->assertSame([1003, 1002, 1001], $ids);
    }

    private function miner(int $eveId, string $updatedAt): array
    {
        return [
            'eve_id' => $eveId,
            'corporation_id' => 10,
            'alliance_id' => 1,
            'name' => 'Miner ' . $eveId,
            'avatar' => '',
            'amount_owed' => 1000,
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ];
    }

    private function jobMinerId(GenerateInvoice $job): int
    {
        $id = new ReflectionProperty($job, 'id');
        $id->setAccessible(true);

        return $id->getValue($job);
    }
}
