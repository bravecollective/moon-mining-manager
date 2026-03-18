<?php

namespace App\Jobs;

use App\Models\Miner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RegenerateInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        // Build the WHERE clause to filter by alliance and/or corporation membership.
        $whitelist_where = [];
        if (config('eve.alliances_whitelist')) {
            $whitelist_where[] = 'alliance_id IN (' . config('eve.alliances_whitelist') . ')';
        }
        if (config('eve.corporations_whitelist')) {
            $whitelist_where[] = 'corporation_id IN (' . config('eve.corporations_whitelist') . ')';
        }
        if (count($whitelist_where)) {
            $whitelist_whereRaw = '(' . implode(' OR ', $whitelist_where) . ')';
        }

        // Figure out the date of the last Monday when invoices should have been generated,
        // and find miners that should have been sent an invoice but weren't.
        $last_monday = date('Y-m-d', strtotime('Monday this week'));
        $debtors = Miner::select('eve_id')->where('amount_owed', '>=', 1)->whereRaw($whitelist_whereRaw)
            ->whereRaw('eve_id NOT IN (SELECT miner_id FROM invoices WHERE DATE(created_at) = "' . $last_monday . '")')
            ->get();
        Log::info(
            'RegenerateInvoices: found ' . count($debtors) .
            ' miners who should have received an invoice but did not'
        );
        $delay_counter = 0;

        foreach ($debtors as $miner) {
            GenerateInvoice::dispatch($miner->eve_id, $delay_counter * 20);
            Log::info(
                'RegenerateInvoices: dispatched job to generate invoice for miner ' . $miner->eve_id .
                ' and send mail ' . ($delay_counter * 20) . ' seconds later'
            );
            $delay_counter++;
        }

    }
}
