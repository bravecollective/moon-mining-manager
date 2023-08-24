<?php

namespace App\Jobs;

use App\Classes\EsiConnection;
use App\Models\Miner;
use App\Models\MiningActivity;
use App\Models\TaxRate;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollRefinery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    /**
     * @var int
     */
    private $observer_id;

    /**
     * @var int
     */
    private $corporation_id;

    /**
     * @var int
     */
    private $page;

    /**
     * Create a new job instance.
     *
     * @param int $observer_id
     * @param int $corporation_id
     * @param int $page
     */
    public function __construct($observer_id, $corporation_id, $page = 1)
    {
        $this->observer_id = $observer_id;
        $this->corporation_id = $corporation_id;
        $this->page = $page;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        if (empty($this->corporation_id)) {
            Log::error('PollRefinery:: called without corporation_id with observer_id ' . $this->observer_id);
            return;
        }

        $esi = new EsiConnection;

        Log::info('PollRefinery: requesting mining activity log for refinery ' .
            $this->observer_id . ', page ' . $this->page);

        $userId = $esi->getPrimeUserOfCorporation($this->corporation_id);
        if ($userId === null) {
            Log::error('PollRefinery:: Prime user not found for corporation ' . $this->corporation_id);
            return;
        }

        // Retrieve the mining activity log page for this refinery.
        $activity_log = $esi->getConnection($userId)->setQueryString([
            'page' => $this->page,
        ])->invoke('get', '/corporation/{corporation_id}/mining/observers/{observer_id}/', [
            'corporation_id' => $esi->getCorporationId($userId),
            'observer_id' => $this->observer_id,
        ]);

        // If this is the first page request, we need to check for multiple pages and generate subsequent jobs.
        if ($this->page == 1 && $activity_log->pages > 1) {
            Log::info('PollRefinery: found more than 1 page of mining data, queuing additional jobs for ' .
                $activity_log->pages . ' total pages');
            $delay_counter = 1;
            for ($i = 2; $i <= $activity_log->pages; $i++) {
                PollRefinery::dispatch($this->observer_id, $this->corporation_id, $i)
                    ->delay(Carbon::now()->addSecond(20 * $delay_counter));
                $delay_counter++;
            }
        }

        Log::info('PollRefinery: received ' . count($activity_log) . ' mining records');

        $new_mining_activity_records = array();
        $miner_ids = array();
        $type_ids = array();

        foreach ($activity_log as $log_entry) {
            if ($log_entry->last_updated >= date('Y-m-d')) {
                continue; // ignore entries from today, they may still change
            }

            $hash = hash(
                'sha1',
                $log_entry->character_id . $this->observer_id . $log_entry->type_id .
                $log_entry->quantity . $log_entry->last_updated
            );

            // Add a new mining activity array to the list.
            $new_mining_activity_records[] = [
                'hash' => $hash,
                'miner_id' => $log_entry->character_id,
                'refinery_id' => $this->observer_id,
                'type_id' => $log_entry->type_id,
                'quantity' => $log_entry->quantity,
                'created_at' => $log_entry->last_updated . ' 23:59:59',
                'updated_at' => $log_entry->last_updated . ' 23:59:59',
            ];

            // Store the miner.
            if (!in_array($log_entry->character_id, $miner_ids)) {
                $miner_ids[] = $log_entry->character_id;
            }

            // Store the ore type.
            if (!in_array($log_entry->type_id, $type_ids)) {
                $type_ids[] = $log_entry->type_id;
            }
        }

        // Insert all of the new mining activity records to the database.
        //MiningActivity::insertIgnore($new_mining_activity_records);
        $ins = MiningActivity::insertOrIgnore($new_mining_activity_records);

        Log::info(
            'PollRefinery: inserted up to ' . count($new_mining_activity_records) . ' new mining activity records'
        );

        // Check if this miner is already known.
        $delay_counter = 1;
        foreach ($miner_ids as $miner_id) {
            $miner = Miner::where('eve_id', $miner_id)->first();
            // If not, create a job to add the new miner entry.
            if (!isset($miner)) {
                Log::info('PollRefinery: unknown miner found, queuing job to retrieve details');
                MinerCheck::dispatch($miner_id)->delay(Carbon::now()->addSeconds($delay_counter * 5));
                $delay_counter++;
            }
        }

        // Check if this ore type exists in the taxes table.
        foreach ($type_ids as $type_id) {
            $tax_rate = TaxRate::where('type_id', $type_id)->first();
            // If not, create and insert it with zero values.
            if (!isset($tax_rate)) {
                $tax_rate = new TaxRate;
                $tax_rate->type_id = $type_id;
                $tax_rate->check_materials = 1;
                $tax_rate->value = 0;
                $tax_rate->tax_rate = 7;
                $tax_rate->updated_by = 0;
                $tax_rate->save();
                Log::info('PollRefinery: unknown ore ' . $type_id . ' found, new tax rate record created');
            }
        }

    }

}
