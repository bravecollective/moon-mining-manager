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

class GenerateRentalInvoices implements ShouldQueue
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
        // Grab all the renters with active agreements
        // that started before the beginning of month
        // and will end after or on the first day of this month
        // and were not yet updated this month
        $contracts = Renter::whereRaw(
            'moon_id IS NOT NULL AND 
            start_date < DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) - 1 DAY) AND
            (
                end_date IS NULL OR 
                end_date >= DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE()) - 1 DAY)
            ) AND
            (
                generate_invoices_job_run IS NULL OR
                generate_invoices_job_run < CONCAT(LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)), " 23:59:59")
            )'
        )->get();

        // Loop through all the renters and send an invoice for the appropriate amount
        // (taking into account partial months).
        $delay_counter = 1;
        $renters = array();
        foreach ($contracts as $contract) {
            $renters[$contract->character_id]['id'] = $contract->character_id;
            $renters[$contract->character_id]['name'] = $contract->character_name;
            $renters[$contract->character_id]['contracts'][] = $contract->id;
        }

        foreach ($renters as $renter) {
            // Queue jobs to create and send the individual invoices.
            GenerateRentalInvoice::dispatch($renter['contracts'], $delay_counter)
                ->delay(Carbon::now()->addSeconds($delay_counter * 10));
            Log::info(
                'GenerateRentalInvoices: dispatched job to generate invoice for renter ' .
                $renter['name'] . ' and send mail in ' . $delay_counter . ' minutes'
            );
            $delay_counter++;
        }
    }
}
