<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Miner;
use App\Models\Template;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    private $id;
    private $mail_delay;

    /**
     * Create a new job instance.
     *
     * @param int $id
     * @return void
     */
    public function __construct($id, $mail_delay = 20)
    {
        $this->id = $id;
        $this->mail_delay = $mail_delay;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        // Retrieve the miner record.
        $miner = Miner::where('eve_id', $this->id)->first(); /* @var Miner $miner */

        // Pick up the invoice template to apply text substitutions.
        $template = Template::where('name', 'weekly_invoice')->first(); /* @var Template $template */

        // Grab the template subject and body.
        $subject = $template->subject;
        $body = $template->body;

        // Replace placeholder elements in email template.
        $subject = str_replace('{date}', date('Y-m-d'), $subject);
        $subject = str_replace('{name}', $miner->name, $subject);
        $subject = str_replace('{amount_owed}', number_format($miner->amount_owed), $subject);
        $body = str_replace('{date}', date('Y-m-d'), $body);
        $body = str_replace('{name}', $miner->name, $body);
        $body = str_replace('{amount_owed}', number_format($miner->amount_owed), $body);
        $mail = array(
            'body' => $body,
            'recipients' => array(
                array(
                    'recipient_id' => $miner->eve_id,
                    'recipient_type' => 'character'
                )
            ),
            'subject' => $subject,
            'approved_cost' => 0,
        );

        // Queue sending the eve mail, spaced at 1-minute intervals to avoid triggering the
        // mail spam limiter (4/min) or database lockups.
        SendEvemail::dispatch($mail)->delay(Carbon::now()->addSeconds($this->mail_delay * 60));
        Log::info('GenerateInvoice: dispatched job to send mail', [
            'recipients' => $mail['recipients'],
            'delay_mins' => $this->mail_delay,
        ]);

        // Write an invoice entry.
        $invoice = new Invoice;
        $invoice->miner_id = $miner->eve_id;
        $invoice->amount = $miner->amount_owed;
        $invoice->save();

        Log::info('GenerateInvoice: saved new invoice for miner', [
            'char_id' => $miner->eve_id,
            'amount' => $miner->amount_owed,
        ]);
    }
}
