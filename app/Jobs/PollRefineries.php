<?php

namespace App\Jobs;

use App\Classes\EsiConnection;
use App\Models\Refinery;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollRefineries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var int
     */
    private $page;

    /**
     * @param int $user_id
     * @param int $page
     */
    public function __construct($user_id, $page = 1)
    {
        $this->user_id = $user_id;
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
        $esi = new EsiConnection;

        // Clear out all claim details for refinery detonations that have happened in the last 24 hours.
        $previous_detonations = DB::update(
            'UPDATE refineries SET claimed_by_primary = NULL, claimed_by_secondary = NULL, ' .
                'updated_at = \'' . date('Y-m-d H:i:s') . '\' ' .
            'WHERE natural_decay_time < NOW() - INTERVAL 1 DAY ' .
                'AND (claimed_by_primary IS NOT NULL OR claimed_by_secondary IS NOT NULL)'
        );
        Log::info('PollRefineries: cleared claimed refinery detonations in the last day', ['count' => $previous_detonations]);

        // Request a list of all of the active mining observers belonging to the corporation.
        $corporation_id = $esi->getCorporationId($this->user_id);
        $mining_observers = $esi->getConnection($this->user_id)
            ->setQueryString(['page' => $this->page])
            ->invoke('get', '/corporation/{corporation_id}/mining/observers/', [
                'corporation_id' => $corporation_id,
            ]);

        // If this is the first page request, we need to check for multiple pages and generate subsequent jobs.
        if ($this->page == 1 && $mining_observers->pages > 1) {
            Log::info('PollRefineries: found more than 1 page of refineries, queuing additional jobs', ['pages'=>$mining_observers->pages]);
            $delayCounter = 1;
            for ($i = 2; $i <= $mining_observers->pages; $i++) {
                PollRefineries::dispatch($this->user_id, $i)->delay(Carbon::now()->addMinutes($delayCounter));
                $delayCounter++;
            }
        }

        Log::info('PollRefineries: found refineries with active asteroid fields', ['count' => count($mining_observers)]);

        // Process the refineries list. For each entry, we want to check and see if it already exists
        // in the database. If it doesn't, we create a new database entry for it.
        // Updates, such as the owner, are performed regularly in PollStructures.
        foreach ($mining_observers as $observer) {
            $refinery = Refinery::where('observer_id', $observer->observer_id)->first();
            if (!isset($refinery)) {
                $refinery = new Refinery;
                $refinery->observer_id = $observer->observer_id;
                $refinery->observer_type = $observer->observer_type;
                $refinery->corporation_id = $corporation_id;
                $refinery->save();
                Log::info('PollRefineries: created new refinery record', ['observer_id' => $observer->observer_id]);
                // Create a new job to fill in the parts we don't know from this response.
                PollStructureData::dispatch($observer->observer_id, $this->user_id);
            }
        }

    }
}
