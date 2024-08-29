<?php

namespace App\Jobs;

use App\Models\Moon;
use App\Models\Renter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateRent implements ShouldQueue
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
        $calc = new \App\Classes\CalculateRent();

        // Grab all of the (available) moons and loop through them.
        $moons = Moon::where('available', 1)->get();
        foreach ($moons as $moon) {
            // Save the current month's rental fee.
            $moon->previous_monthly_rental_fee = $moon->monthly_rental_fee;
            $moon->previous_monthly_corp_rental_fee = $moon->monthly_corp_rental_fee;

            // update - saves the $moon object
            $fee = $calc->updateMoon($moon, Renter::TYPE_ACTIVE);
            $corpFee = $calc->updateMoon($moon, Renter::TYPE_PASSIVE);

            Log::info("CalculateRent: updated stored monthly rental fee for moon $moon->id to $fee/$corpFee");

            // Update the monthly rent figure if this moon is currently rented.
            DB::table('renters')
                ->where('moon_id', $moon->id)
                ->where('type', Renter::TYPE_ACTIVE)
                ->update(['monthly_rental_fee' => $fee]);
            DB::table('renters')
                ->where('moon_id', $moon->id)
                ->where('type', Renter::TYPE_PASSIVE)
                ->update(['monthly_rental_fee' => $corpFee]);
        }
    }
}
