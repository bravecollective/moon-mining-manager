<?php

namespace App\Jobs;

use App\Classes\EsiConnection;
use App\Models\Renter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRenterDelinquencyList implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        // ESI connection.
        $esi = new EsiConnection;

        // Grab all the renters with active agreements where they currently have an outstanding balance.
        $renters = Renter::whereRaw(
            'moon_id IS NOT NULL && start_date <= CURDATE() ' .
            'AND (end_date IS NULL OR end_date >= CURDATE()) AND amount_owed > 0'
        )->get(); /* @var Renter[] $renters*/

        // Generate the email to send to the administrator.
        $subject = 'Moon Rental Delinquency Report for ' . date('Y-m-d');
        $body = "The following renters have not paid off their outstanding rental balance yet:\n\n";
        foreach ($renters as $renter) {
            // Request the character name for this rental agreement.
            $character = $esi->getConnection()->invoke('get', '/characters/{character_id}/', [
                'character_id' => $renter->character_id,
            ]);

            // Grab a reference to the refinery/moon that is being rented.
            $nameRented = $renter->getRentedName();
            if ($nameRented === null) {
                Log::info("SendRenterDelinquencyList: Renter $renter->id without moon, aborting.");
                return;
            }

            // Output the details of this renter to the email body.
            $url = config('app.url');
            $body .= "Renter: <url=showinfo:1376//$renter->character_id>$character->name</url>\n";
            $body .= "Refinery: <loc><url=$url$renter->refinery_id>$nameRented</url></loc>\n";
            $body .= 'Balance: ' . number_format($renter->amount_owed) . "ISK\n\n";
        }
        $mail = array(
            'body' => $body,
            'recipients' => array(
                array(
                    'recipient_id' => config('eve.admin_user_id'),
                    'recipient_type' => 'character'
                )
            ),
            'subject' => $subject,
            'approved_cost' => 0,
        );

        // Queue sending the evemail, spaced at 1 minute intervals to avoid triggering the mailspam limiter (4/min).
        SendEvemail::dispatch($mail);
        Log::info('SendRenterDelinquencyList: dispatched job to send mail', [
            'mail' => $mail,
        ]);
    }
}
