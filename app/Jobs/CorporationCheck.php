<?php

namespace App\Jobs;

use App\Models\Alliance;
use App\Classes\EsiConnection;
use App\Models\Corporation;
use App\Models\Miner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CorporationCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 3 * 60;

    private $ids;
    private $conn;

    /**
     * Create a new job instance.
     *
     * @param array array of miner ids
     */
    public function __construct($ids)
    {
        $this->ids = $ids;

        $esi = new EsiConnection;
        $this->conn = $esi->getConnection();
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        // batch ids into a single request
        $affiliations = $this->conn->setBody($this->ids)->invoke('post', '/characters/affiliation/');

        $uniq_corporations = $this->reduceCorporations($affiliations->getArrayCopy());

        // make all corporation requests at once to save unneeded re-fetching
        $start = microtime(true);
        Log::info('CorporationCheck: fetching corp data', ['count' => count($uniq_corporations)]);
        $corporations = array();
        foreach ($uniq_corporations as $corp) {
            $corporations[$corp->corporation_id] = $this->conn->invoke(
                'get',
                '/corporations/{corporation_id}',
                [
                    'corporation_id' => $corp->corporation_id
                ]
            );
        }

        // The above loop is currently the long pole for this job. frequently taking 40+ seconds
        Log::info('CorporationCheck: done fetching corp data', ['t' => microtime(true)-$start]);

        $changed_miners = 0;
        foreach ($affiliations as $affiliation) {
            $id = $affiliation->character_id;
            $corporation_id = $affiliation->corporation_id;
            $corporation = $corporations[$corporation_id];

            // Check if the miner already exists.
            /* @var Miner $miner */
            $miner = Miner::where('eve_id', $id)->first();

            // if they are changed later we save to db
            $changed = false;

            $ctx = [
                'name' => $miner->name,
                'char_id' => $id,
            ];

            // most characters live in doomheim when they are deleted
            if ($affiliation->corporation_id === 1000001) {
                Log::info('CorporationCheck: miner is in Doomheim', $ctx);
                continue; // bail
            }

            // Insert new corporation if we don't know about it.
            $existing_corporation = Corporation::where('corporation_id', $corporation_id)->first();
            if (!isset($existing_corporation)) {
                $new_corporation = new Corporation;
                $new_corporation->corporation_id = $corporation_id;
                $new_corporation->name = $corporation->name;
                $new_corporation->save();

                Log::info('CorporationCheck: stored new corporation', [
                    'corp_name' => $new_corporation->name,
                    'corp_id' => $new_corporation->corporation_id,
                    'alliance_id' => $corporation->alliance_id,
                ]);
            }

            // Insert new alliance if we don't know about it.
            if (isset($corporation->alliance_id)) {
                $existing_alliance = Alliance::where('alliance_id', $corporation->alliance_id)->first();
                if (!isset($existing_alliance)) {
                    $alliance = $this->conn->invoke('get', '/alliances/{alliance_id}/', [
                        'alliance_id' => $corporation->alliance_id,
                    ]);

                    // This is a new alliance, save the details.
                    $new_alliance = new Alliance;
                    $new_alliance->alliance_id = $corporation->alliance_id;
                    $new_alliance->name = $alliance->name;
                    $new_alliance->save();

                    Log::info('CorporationCheck: stored new alliance', [
                        'alliance_name' => $new_alliance->name,
                        'alliance_id' => $new_alliance->alliance_id,
                    ]);
                }
            }

            $ctx['corp_id'] = $miner->corporation_id;
            $ctx['alliance_id'] = $miner->alliance_id;

            // Check if they are still in the same corporation as last time we checked.
            if ($miner->corporation_id != $corporation_id) {
                $changed = true;
                $miner->corporation_id = $corporation_id;
                $ctx['new_corp_id'] = $corporation_id;
            }

            if (!isset($corporation->alliance_id)) {
                $changed = true;
                $miner->alliance_id = null;
                $ctx['new_alliance_id'] = null;
            } else if ($miner->alliance_id != $corporation->alliance_id) {
                $changed = true;
                $miner->alliance_id = $corporation->alliance_id;
                $ctx['new_alliance_id'] = $corporation->alliance_id;
            }

            if (!$changed) {
                Log::info('CorporationCheck: miner unchanged', $ctx);
            } else {
                $miner->save();
                $changed_miners++;
                Log::info('CorporationCheck: miner changed', $ctx);
            }
        }

        Log::info('CorporationCheck: batch complete', ['changed_miners' => $changed_miners]);
    }

    private function reduceCorporations(array $objects): array
    {
        $uniqueCorporations = [];

        // Iterate over the objects and add them to the set based on corporation_id
        foreach ($objects as $obj) {
            $uniqueCorporations[$obj->corporation_id] = $obj;
        }

        // Convert the set back to a simple indexed array
        return array_values($uniqueCorporations);
    }
}
