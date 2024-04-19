<?php

namespace App\Jobs;

use App\Models\Miner;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CorporationChecks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        // Grab all of the miner records we have, and loop through them all to queue jobs
        // to check their corporation membership.
        $miners = Miner::all();

        Log::info('CorporationChecks: found ' . count($miners) . ' miners in the database');

        $delay_counter = 1;

        // break up the miners into pages of 1k results
        $pages = $this->paginateIterable($miners);

        foreach($pages as $page) {
            // we need an array of id ints
            $ids = array_map(fn($miner) => intval($miner->eve_id), $page);

            CorporationCheck::dispatch($ids)->delay(Carbon::now()->addSecond(15 * $delay_counter));

            $delay_counter++;
        }
    }

    private function paginateIterable(iterable $iterable, int $pageSize = 1000): iterable
    {
        $currentPage = [];
        $count = 0;

        foreach ($iterable as $item) {
            $currentPage[] = $item;
            $count++;

            if ($count === $pageSize) {
                yield $currentPage;
                $currentPage = [];
                $count = 0;
            }
        }

        if ($count > 0) {
            yield $currentPage;
        }
    }

}
