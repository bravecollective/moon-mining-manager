<?php

namespace App\Jobs;

use App\Models\Renter;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateRentNotifications implements ShouldQueue
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
        // Grab all the renters with active agreements.
        $renters = Renter::whereRaw(
            'moon_id IS NOT NULL && start_date <= CURDATE() AND (end_date IS NULL OR end_date >= CURDATE())'
        )->get();

        // Loop through all the renters and send a notification to them about next month's rent amount.
        $delay_counter = 1;
        foreach ($renters as $renter) {
            // Queue jobs to create and send the individual mails.
            GenerateRentNotification::dispatch($renter->id, $delay_counter)
                ->delay(Carbon::now()->addSeconds($delay_counter * 10));
            Log::info('GenerateRentNotifications: dispatched job to generate mail for renter', [
                'char_id' => $renter->character_id,
                'delay_mins' => $delay_counter,
            ]);
            $delay_counter++;
        }
    }
}
