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

    private $affiliation;

    /**
     * Create a new job instance.
     *
     * @param object affiliation
     */
    public function __construct($affiliation)
    {
        $this->affiliation = $affiliation;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $esi = new EsiConnection;
        $conn = $esi->getConnection();

        $id = $this->affiliation->character_id;
        $corporation_id = $this->affiliation->corporation_id;

        // Check if the miner already exists.
        /* @var Miner $miner */
        $miner = Miner::where('eve_id', $id)->first();
        $changed = false;

        Log::info('CorporationCheck: checking miner ' . $id);

        // most characters live in doomheim when they are deleted
        if ($this->affiliation->corporation_id === 1000001) {
            Log::info("". $id ." is in Doomheim, purging.");

            $miner->delete();

            return;
        }

        // Retrieve all of the relevant details for the corporation.
        $corporation = $conn->invoke('get', '/corporations/{corporation_id}/', [
            'corporation_id' => $corporation_id,
        ]);

        // Check if they are still in the same corporation as last time we checked.
        if ($miner->corporation_id == $corporation_id) {
            Log::info(
                'CorporationCheck: miner ' . $this->miner_id . ' is still in the same corporation ' .
                $corporation_id
            );
        } else {
            // Update the miner's stored corporation ID.
            $miner->corporation_id = $corporation_id;

            Log::info(
                'CorporationCheck: miner ' . $this->miner_id . ' has moved to corporation ' .
                $corporation_id
            );

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
                    $existing_alliance = Alliance::where('alliance_id', $corporation->alliance_id)->first();

                    if (!isset($existing_alliance)) {
                        // This is a new alliance, save the details.
                        $new_alliance = new Alliance;
                        $new_alliance->alliance_id = $corporation->alliance_id;
                        $alliance = $conn->invoke('get', '/alliances/{alliance_id}/', [
                            'alliance_id' => $corporation->alliance_id,
                        ]);
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
                ' for miner ' . $this->miner_id
            );
        }

        if ($changed) {
            // Save the updated miner record.
            $miner->save();
        }
    }
}
