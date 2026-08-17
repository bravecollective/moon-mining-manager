<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Classes\EsiConnection;
use App\Models\Refinery;
use Illuminate\Support\Facades\Log;

class PollExtractions implements ShouldQueue
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

        Log::info('PollExtractions: clearing all extraction data more than 2 days old');

        // Delete any extraction data that relates to periods that have already passed
        // (the field natural decay time + 2 days).
        $cutoff = date('Y-m-d H:m:s', time() - (2 * 24 * 60 * 60));
        Refinery::where('natural_decay_time', '<', $cutoff)->update([
            'extraction_start_time' => NULL,
            'chunk_arrival_time' => NULL,
            'natural_decay_time' => NULL,
        ]);

        // Request all active extraction cycle information for the prime user's corporation.
        $timers = $esi->getConnection($this->user_id)
            ->setQueryString(['page' => $this->page])
            ->invoke('get', '/corporation/{corporation_id}/mining/extractions/', [
                'corporation_id' => $esi->getCorporationId($this->user_id),
            ]);

        // If this is the first page request, we need to check for multiple pages and generate subsequent jobs.
        if ($this->page == 1 && $timers->pages > 1) {
            Log::info('PollExtractions: more than 1 page found, queuing additional jobs', [
                'pages' => $timers->pages,
            ]);
            $delayCounter = 1;
            for ($i = 2; $i <= $timers->pages; $i++) {
                PollExtractions::dispatch($this->user_id, $i)->delay(Carbon::now()->addMinutes($delayCounter));
                $delayCounter++;
            }
        }

        // Loop through all the extraction data, updating the current status and time remaining
        // for any active extraction cycles.
        foreach ($timers as $timer) {
            $refinery = Refinery::where('observer_id', $timer->structure_id)->first(); /* @var Refinery $refinery */
            $refinery->extraction_start_time = $this->convertTimestampFormat($timer->extraction_start_time);
            $refinery->chunk_arrival_time = $this->convertTimestampFormat($timer->chunk_arrival_time);
            $refinery->natural_decay_time = $this->convertTimestampFormat($timer->natural_decay_time);
            $refinery->save();
            Log::info('PollExtractions: saved current extraction timestamps', ['structure_id' => $timer->structure_id]);
        }
    }

    /**
     * Convert from ISO 8601 timestamp format to MySQL TIMESTAMP format.
     *
     * @param string $timestamp
     * @return string
     */
    private function convertTimestampFormat($timestamp)
    {
        return str_replace('T', ' ', substr($timestamp, 0, 19));
    }
}
