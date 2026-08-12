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

class GenerateInvoices implements ShouldQueue
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

        // For all miners in your whitelisted alliances/corporations that currently
        // owe an outstanding balance, queue a job to generate and send an invoice.
        $debtors = Miner::where('amount_owed', '>=', 1000)
            ->whereRaw($whitelist_whereRaw)
            ->select('miners.*')
            ->selectSub(function ($query) {
                $query->from('mining_activities')
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('miner_id', 'miners.eve_id');
            }, 'last_activity_at')
            ->orderByRaw('COALESCE(last_activity_at, miners.updated_at) DESC')
            ->orderBy('miners.eve_id')
            ->get();
        Log::info(
            'GenerateInvoices: found ' . count($debtors) .
            ' miners with an outstanding balance over 1,000 ISK to be invoiced'
        );
        $delay_counter = 1;

        foreach ($debtors as $miner) {
            GenerateInvoice::dispatch($miner->eve_id, $delay_counter)
                ->delay(Carbon::now()->addSeconds($delay_counter * 10));
            Log::info(
                'GenerateInvoices: dispatched job to generate invoice for miner ' . $miner->eve_id .
                ' and send mail ' . $delay_counter . ' minutes later'
            );
            $delay_counter++;
        }

    }

}
