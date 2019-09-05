<?php

namespace App\Jobs;

use App\Models\Miner;
use App\Models\MiningActivity;
use App\Models\Refinery;
use App\Models\TaxRate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMiningActivity implements ShouldQueue
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

        Log::info('ProcessMiningActivity: starting...');

        // Create arrays to hold miner and refinery details. We'll write it back to the database when we're done.
        $miner_data = [];
        $refinery_data = [];

        // Grab all of the ore values and tax rates to refer to in calculations. This
        // returns an array keyed by type_id, so individual values/tax rates can be returned
        // by reference to $tax_rates[type_id]->value or $tax_rates[type_id]->tax_rate.
        $tax_rates = TaxRate::select(['type_id', 'value', 'tax_rate'])->get()->keyBy('type_id');

        // Grab all of the unprocessed mining activity records from the last day and loop through them.
        /** @var MiningActivity[] $activity */
        $activity = MiningActivity::where('processed', 0)->get();

        Log::info('ProcessMiningActivity: found ' . count($activity) . ' mining activity entries to process');

        foreach ($activity as $entry) {
            // Each mining activity relates to a single ore type.
            // We calculate the total value of that activity, and apply the 
            // current tax rate to derive a tax amount to charge.
            $total_value = $entry->quantity * $tax_rates[$entry->type_id]->value;

            // divide by 100 because the moon ore comes in stacks of 100
            $tax_amount = $total_value * $tax_rates[$entry->type_id]->tax_rate / 100;

            // Add the tax amount for this entry to the miner array.
            if (isset($miner_data[$entry->miner_id])) {
                $miner_data[$entry->miner_id] += $tax_amount;
            } else {
                $miner_data[$entry->miner_id] = $tax_amount;
            }

            // Add the income for this entry to the refinery array.
            if (isset($refinery_data[$entry->refinery_id])) {
                $refinery_data[$entry->refinery_id] += $tax_amount;
            } else {
                $refinery_data[$entry->refinery_id] = $tax_amount;
            }

            // Save the tax amount for the specific mining entry.
            $entry->tax_amount = $tax_amount;
            $entry->processed = 1;
            $entry->save();
        }

        // Loop through all of the miner data and update the database records.
        if (count($miner_data)) {
            foreach ($miner_data as $key => $value) {
                // We don't need to check if this miner exists, since they will all have been
                // created during the PollRefinery job.
                /** @var Miner $miner */
                $miner = Miner::where('eve_id', $key)->first();
                $miner->amount_owed += $value;
                $miner->save();
                Log::info(
                    'ProcessMiningActivity: updated stored amount owed by miner ' . $key .
                    ' by ' . number_format($value, 0) . ' ISK, new total is ' .
                    number_format($miner->amount_owed, 0) . ' ISK'
                );
            }
        }

        // Loop through all the refinery data and update the database records.
        if (count($refinery_data)) {
            foreach ($refinery_data as $key => $value) {
                $refinery = Refinery::where('observer_id', $key)->first();
                $refinery->income += $value;
                $refinery->save();
                Log::info(
                    'ProcessMiningActivity: updated stored amount generated by refinery ' . $key .
                    ' to ' . number_format($refinery->income, 0) . ' ISK'
                );
            }
        }

    }

}
