<?php

namespace App\Jobs;

use App\Classes\EsiConnection;
use App\Models\Corporation;
use App\Models\Refinery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollStructureData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    /**
     * @var int
     */
    private $structure_id;

    /**
     * @var int
     */
    private $user_id;

    /**
     * Create a new job instance.
     *
     * @param int $structure_id
     * @param int $user_id
     */
    public function __construct($structure_id, $user_id)
    {
        $this->structure_id = $structure_id;
        $this->user_id = $user_id;
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
        $conn = $esi->getConnection($this->user_id);

        // Pull down additional information about this structure.
        $structure = $conn->invoke('get', '/universe/structures/{structure_id}/', [
            'structure_id' => $this->structure_id,
        ]);

        // Update the refinery item with the new information.
        $refinery = Refinery::where('observer_id', $this->structure_id)->first(); /* @var Refinery $refinery */
        $refinery->name = $structure->name;
        $refinery->solar_system_id = $structure->solar_system_id;
        $refinery->corporation_id = $structure->owner_id;
        $refinery->save();

        Log::info('PollStructureData: updated refinery', ['structure_id'=> $this->structure_id]);

        // Check if we know the corporation already.
        $existingCorporation = Corporation::where('corporation_id', $refinery->corporation_id)->first();
        if ($existingCorporation === null) {

            // This is a new corporation, retrieve all of the relevant details.
            $corporation = $conn->invoke('get', '/corporations/{corporation_id}/', [
                'corporation_id' => $refinery->corporation_id,
            ]);
            $new_corporation = new Corporation;
            $new_corporation->corporation_id = $refinery->corporation_id;
            $new_corporation->name = $corporation->name;
            $new_corporation->save();

            Log::info('PollStructureData: stored new corporation', ['corp_id' => $corporation->name]);
        }
    }
}
