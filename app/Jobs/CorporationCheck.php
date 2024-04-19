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

    public $tries = 10;

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
        // batch those ids into a single request
        $affiliations = $this->conn->setBody($this->ids)->invoke('post', '/characters/affiliation/');

        $uniq_corporations = $this->reduceCorporations($affiliations);

        // make all corporation requests at once to save unneeded re-fetching
        $corporations = array_map(function ($corp_id) {
            return [
                $corp_id => $this->conn->invoke('get', '/corporations/{corporation_id}', [
                    'corporation_id' => $corp_id
                ])
            ];
        }, $uniq_corporations);

        foreach( $affiliations as $affiliation ) {
            $id = $affiliation->character_id;
            $corporation_id = $affiliation->corporation_id;
            $corporation = $corporations[$corporation_id];

            // Check if the miner already exists.
            /* @var Miner $miner */
            $miner = Miner::where('eve_id', $id)->first();

            // if they are changed later we save to db
            $changed = false;

            Log::info('CorporationCheck: checking miner ' . $id);

            // most characters live in doomheim when they are deleted
            if ($affiliation->corporation_id === 1000001) {
                Log::info("". $id ." is in Doomheim, purging.");

                $miner->delete();

                continue; // bail
            }

            // Check if they are still in the same corporation as last time we checked.
            if ($miner->corporation_id == $corporation_id) {
                Log::info(
                    'CorporationCheck: miner ' . $id . ' is still in the same corporation ' .
                    $corporation_id
                );
            } else {
                // Update the miner's stored corporation ID.
                $miner->corporation_id = $corporation_id;

                Log::info(
                    'CorporationCheck: miner ' . $id . ' has moved to corporation ' .
                    $corporation_id
                );

                // TODO: this can be moved up
                // Check if they have moved to another corporation we know about already.
                $existing_corporation = Corporation::where('corporation_id', $corporation_id)->first();

                if (!isset($existing_corporation)) {
                    $new_corporation = new Corporation;
                    $new_corporation->corporation_id = $corporation_id;
                    $new_corporation->name = $corporation->name;
                    $new_corporation->save();

                    Log::info('CorporationCheck: stored new corporation ' . $corporation->name);

                    // Check if their new corporation is a different alliance.
                    if (isset($corporation->alliance_id)) {
                        $miner->alliance_id = $corporation->alliance_id;

                        // TODO: this can be moved up
                        $existing_alliance = Alliance::where('alliance_id', $corporation->alliance_id)->first();

                        if (!isset($existing_alliance)) {
                            // TODO: this can be batched to avoid re-fetching previously fetched data
                            $alliance = $this->conn->invoke('get', '/alliances/{alliance_id}/', [
                                'alliance_id' => $corporation->alliance_id,
                            ]);

                            // This is a new alliance, save the details.
                            $new_alliance = new Alliance;
                            $new_alliance->alliance_id = $corporation->alliance_id;
                            $new_alliance->name = $alliance->name;
                            $new_alliance->save();

                            Log::info('CorporationCheck: stored new alliance ' . $alliance->name);
                        }
                    }

                }

                $changed = true;
            }

            if (isset($corporation->alliance_id) && $miner->alliance_id != $corporation->alliance_id) {
                $miner->alliance_id = $corporation->alliance_id;

                $changed = true;

                Log::info(
                    'CorporationCheck: updated alliance ' . $corporation->alliance_id .
                    ' for miner ' . $id
                );
            }

            if ($changed) {
                // Save the updated miner record.
                $miner->save();
            }
        }
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
