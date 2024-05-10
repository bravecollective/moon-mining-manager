<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Classes\EsiConnection;
use App\Models\Miner;
use App\Models\Corporation;
use App\Models\Alliance;
use Illuminate\Support\Facades\Log;

class MinerCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    private $miner_id;

    /**
     * Create a new job instance.
     *
     * @param int $id
     * @return void
     */
    public function __construct($id)
    {
        $this->miner_id = $id;
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

        // Check if the miner already exists.
        $existing_miner = Miner::where('eve_id', $this->miner_id)->first();

        // If not, create a new entry, including pulling additional information.
        if (!isset($existing_miner)) {
            Log::info('MinerCheck: new miner ' . $this->miner_id . ' found, creating new record');
            $miner = new Miner;
            $miner->eve_id = $this->miner_id;
            $character = $conn->invoke('get', '/characters/{character_id}/', [
                'character_id' => $this->miner_id,
            ]);
            $miner->name = $character->name;

            $req = $conn->setBody([
                intval($miner->eve_id)
            ])->invoke('post', '/characters/affiliation/');
            $affiliations = current($req->getArrayCopy());
            $miner->corporation_id = $affiliations->corporation_id;

            $portrait = $conn->invoke('get', '/characters/{character_id}/portrait/', [
                'character_id' => $this->miner_id,
            ]);
            $miner->avatar = $portrait->px128x128;
            $corporation = $conn->invoke('get', '/corporations/{corporation_id}/', [
                'corporation_id' => $affiliations->corporation_id,
            ]);
            if (isset($corporation->alliance_id)) {
                $miner->alliance_id = $corporation->alliance_id;
            }
            $miner->save();
            Log::info('MinerCheck: saved new miner ' . $miner->eve_id . ' from corporation ' . $miner->corporation_id);
            // Also retrieve the corporation and alliance names for use in reporting.
            $existing_corporation = Corporation::where('corporation_id', $affiliations->corporation_id)->first();
            if (!isset($existing_corporation)) {
                $new_corporation = new Corporation;
                $new_corporation->corporation_id = $affiliations->corporation_id;
                $new_corporation->name = $corporation->name;
                $new_corporation->save();
                Log::info('MinerCheck: stored new corporation ' . $affiliations->corporation_id);
            }
            if (isset($corporation->alliance_id)) {
                $existing_alliance = Alliance::where('alliance_id', $corporation->alliance_id)->first();
                if (!isset($existing_alliance)) {
                    $new_alliance = new Alliance;
                    $new_alliance->alliance_id = $corporation->alliance_id;
                    $alliance = $conn->invoke('get', '/alliances/{alliance_id}/', [
                        'alliance_id' => $corporation->alliance_id,
                    ]);
                    $new_alliance->name = $alliance->name;
                    $new_alliance->save();
                    Log::info('MinerCheck: stored new alliance ' . $corporation->alliance_id);
                }
            }
        }

    }

}
