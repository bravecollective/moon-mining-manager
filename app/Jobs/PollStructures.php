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
use Illuminate\Support\Facades\Log;

class PollStructures implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var int
     */
    private $page;

    /**
     * Create a new job instance.
     *
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

        Log::info('PollStructures: requesting corporation structures', ['page' => $this->page]);

        // Request all corporation structures of the prime user's corporation.
        $structures = $esi->getConnection($this->user_id)->setQueryString([
            'page' => $this->page,
        ])->invoke('get', '/corporations/{corporation_id}/structures/', [
            'corporation_id' => $esi->getCorporationId($this->user_id),
        ]);

        // If this is the first page request, we need to check for multiple pages and generate subsequent jobs.
        if ($this->page == 1 && $structures->pages > 1) {
            Log::info('PollStructures: found more than 1 page of corporation structures, queuing additional jobs', ['pages' => $structures->pages]);
            $delay_counter = 1;
            for ($i = 2; $i <= $structures->pages; $i++) {
                PollStructures::dispatch($this->user_id, $i)->delay(Carbon::now()->addMinutes($delay_counter));
                $delay_counter++;
            }
        }

        // Loop through all the structures, looking for Athanors or Tataras.
        $refineries = array(
            35835, // Athanor
            35836, // Tatara
        );
        $delay_counter = 1;
        foreach ($structures as $structure) {
            if (in_array($structure->type_id, $refineries)) {
                // Found a refinery. If it doesn't already exist, create a record for it.
                $refinery = Refinery::where('observer_id', $structure->structure_id)->first();
                if (!isset($refinery)) {
                    $refinery = new Refinery;
                    $refinery->observer_id = $structure->structure_id;
                    $refinery->observer_type = 'structure';
                    $refinery->corporation_id = $structure->corporation_id;
                    $refinery->save();
                    Log::info('PollStructures: created new refinery record', ['structure_id' => $structure->structure_id]);
                }

                // Create a new job to fetch or update the parts we don't get from this response,
                // it also updates the owner.
                PollStructureData::dispatch($structure->structure_id, $this->user_id)
                    ->delay(Carbon::now()->addMinutes($delay_counter));
                $delay_counter++;
            }
        }

    }

}
