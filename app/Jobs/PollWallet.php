<?php

namespace App\Jobs;

use App\Classes\EsiConnection;
use App\Models\Miner;
use App\Models\Payment;
use App\Models\RentalPayment;
use App\Models\Renter;
use App\Models\Template;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Seat\Eseye\Eseye;

class PollWallet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var int
     */
    private $page;

    /**
     * @var Eseye
     */
    private $conn;

    /**
     * @var int
     */
    private $delay_counter;

    /**
     * @var string yyyy-mm-dd
     */
    private $date;

    /**
     * @param int $userId
     * @param int $page
     * @param string $date
     */
    public function __construct($userId, $page = 1, $date = null)
    {
        $this->userId = $userId;
        $this->page = $page;
        $this->date = (string)$date;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        if (empty($this->userId)) { // it happened, not sure why
            Log::error('PollWallet:: called without userId.');
            return;
        }

        $esi = new EsiConnection;
        $this->conn = $esi->getConnection($this->userId);
        $corporationId = $esi->getCorporationId($this->userId);

        Log::info('PollWallet: Retrieving transactions, corporation ' . $corporationId . ', page ' . $this->page);

        // Request the transactions from the master wallet division.
        $transactions = $this->conn->setQueryString([
            'page' => $this->page,
        ])->invoke('get', '/corporations/{corporation_id}/wallets/{division}/journal/', [
            'corporation_id' => $corporationId,
            'division' => 1, // master wallet
        ]);

        Log::info('PollWallet: retrieved ' . count($transactions) . ' transactions from the corporation wallet');

        $this->delay_counter = 1;
        $date = NULL;
        $pollNextPage = $transactions->pages > $this->page;

        foreach ($transactions as $transaction) {
            if ($transaction->ref_type != 'player_donation') {
                continue;
            }

            $ref_id = $transaction->id;
            $date = date('Y-m-d', strtotime($transaction->date));

            // Checks to see if this donation was already processed.
            $payment = Payment::where('ref_id', $ref_id)->first();
            if ($payment) {
                $pollNextPage = false;
                continue;
            }
            $rental_payment = RentalPayment::where('ref_id', $ref_id)->first();
            if ($rental_payment) {
                $pollNextPage = false;
                continue;
            }

            // Look for matching payers among renters and miners.
            $contracts = Renter::where([
                ['character_id', $transaction->first_party_id],
                ['amount_owed', '>', 0.0],
                ])->get(); /* @var Renter $renter */
            $miner = Miner::where('eve_id', $transaction->first_party_id)->first(); /* @var Miner $miner */

            $payment_pool = $transaction->amount;
            if ($this->userId == env('RENT_CORPORATION_PRIME_USER_ID') && isset($contracts)) {
                foreach ($contracts as $contract) {
                    $amount_paid = min($contract->amount_owed, $payment_pool);
                    if ($amount_paid <= 0.0) {
                        continue;
                    }
                    $payment_pool -= $amount_paid;
                    $this->processRents($transaction, $amount_paid, $contract, $ref_id);
                }
            }

            // Next, if this donation is actually from a recognised miner (and wasn't already processed).
            if ($payment_pool > 0.0 && $this->userId == env('TAX_CORPORATION_PRIME_USER_ID') && isset($miner)) {
                $this->processTaxes($transaction, $payment_pool, $miner, $date, $ref_id);
            }
            if ($payment_pool > 0.0) {
                Log::warning('transaction amount not entirely applied ' . json_encode($transaction));
            }
        }

        // poll next page?
        if ($pollNextPage && $date !== null && $date >= '2019-01-01') {
            Log::info(
                'PollWallet: queued job to poll page ' . ($this->page + 1) .
                ' in ' . $this->delay_counter . ' minutes'
            );
            PollWallet::dispatch($this->userId, $this->page + 1, $this->date)
                ->delay(Carbon::now()->addMinutes($this->delay_counter));
        }

        // If the last transaction date is not earlier than a specified date, request the next page of wallet results,
        // but never go back further than 2019-01-01.
        elseif ($this->date !== '' && $date !== null && $date >= $this->date && $date >= '2019-01-01') {
            Log::info(
                'PollWallet: Date ' . $date . ' is greater than ' . $this->date .
                ', repolling for any earlier transactions'
            );
            PollWallet::dispatch($this->userId, $this->page + 1, $this->date)
                ->delay(Carbon::now()->addMinutes($this->delay_counter));
        }
    }

    /**
     * @param \stdClass $transaction
     * @param Renter $renter
     * @param int $ref_id
     * @throws \Exception
     */
    private function processRents($transaction, $amount_paid, Renter $renter, $ref_id)
    {
        // Record this transaction in the rental_payments table.
        $payment = new RentalPayment;
        $payment->renter_id = $transaction->first_party_id;
        $payment->refinery_id = $renter->refinery_id;
        $payment->moon_id = $renter->moon_id;
        $payment->ref_id = $ref_id;
        $payment->amount_received = $amount_paid;
        $payment->save();

        // Clear their outstanding debt.
        $renter->amount_owed -= $amount_paid;
        $renter->save();
        Log::info(
            'PollWallet: saved a new payment from renter ' . $renter->character_id .
            ' at refinery ' . $renter->refinery_id . '/moon ' . $renter->moon_id .
            ' for ' . $amount_paid
        );

        // Retrieve the name of the character.
        $character = $this->conn->invoke('get', '/characters/{character_id}/', [
            'character_id' => $renter->character_id,
        ]);

        $this->dispatchMail($character->name, $transaction, $renter->amount_owed, $renter->character_id, 'rental');
    }

    /**
     * @param \stdClass $transaction
     * @param Miner $miner
     * @param string $date
     * @param int $ref_id
     */
    private function processTaxes($transaction, $payment_pool, Miner $miner, $date, $ref_id)
    {
        Log::info('PollWallet: found a player donation of ' . $payment_pool .
            ' ISK from a recognised miner ' . $miner->eve_id . ' on ' . $date . ', reference ' . $ref_id);

        // Parse the 'reason' entered by the player to see if they want to pay off other players/alts bills.
        if (isset($transaction->reason)) {
            $reason = $transaction->reason;
        }
        if (isset($reason) && strlen($reason) > 0) {
            // Split by commas.
            $elements = explode(',', $reason);
            $recipients = []; /* @var Miner[] $recipients */

            // For each element found, test it to see if it is a character ID or name, and find a
            // reference to the relevant miner.
            foreach ($elements as $element) {
                if (preg_match('/^\d+$/', trim($element))) {
                    $recipient_miner = Miner::where('eve_id', trim($element))->first();
                } else {
                    $recipient_miner = Miner::where('name', trim($element))->first();
                }
                if (isset($recipient_miner)) {
                    $recipients[] = $recipient_miner;
                }
            }
            Log::info(
                'PollWallet: detected player-entered reason for payment, ' .
                    'parsed for alternative recipients of payment, found ' .
                    count($recipients) . ' additional valid recipients.',
                [
                    'recipients' => array_map(function ($recipient) {
                        return ['eve_id' => $recipient['eve_id'], 'name' => $recipient['name']];
                    }, $recipients),
                    'reason' => $reason
                ]
            );

            // If any valid recipients were found, create payments to them.
            foreach ($recipients as $recipient) {
                // Calculate how much to pay off for this recipient - either the full amount, or whatever
                // is left of the balance.
                $payment_amount = min($recipient->amount_owed, $payment_pool)

                // Update the remaining balance of what was sent.
                $payment_pool -= $payment_amount;

                // Record the transaction in the payments table.
                $payment = new Payment;
                $payment->miner_id = $recipient->eve_id;
                $payment->ref_id = $ref_id;
                $payment->amount_received = $payment_amount;
                $payment->save();
                Log::info('PollWallet: saved a new payment from miner ' . $miner->eve_id .
                    ' on behalf of miner ' . $recipient->eve_id . ' for ' . $payment_amount);

                // Deduct the amount from the recipient's outstanding balance.
                if ($recipient->id == $miner->id) {
                    $miner->amount_owed -= $payment_amount;
                    $miner->save();
                } else {
                    $recipient->amount_owed -= $payment_amount;
                    $recipient->save();
                }
            }
        }

        // If there is any money left to apply to the donator's balance after paying other recipients.
        if ($payment_pool > 0) {
            // Record this transaction in the payments table.
            $payment = new Payment;
            $payment->miner_id = $transaction->first_party_id;
            $payment->ref_id = $ref_id;
            $payment->amount_received = $payment_pool;
            $payment->save();

            Log::info('PollWallet: saved a new payment from miner ' . $miner->eve_id . ' for ' . $payment_pool);

            // Deduct the amount from their outstanding balance.
            $miner->amount_owed -= $payment_pool;
            $miner->save();
        }

        $this->dispatchMail($miner->name, $transaction, $miner->amount_owed, $miner->eve_id, 'tax');
    }

    /**
     * @param string $name
     * @param \stdClass $transaction
     * @param float $amountOwed
     * @param int $recipientCharacterId
     */
    private function dispatchMail($name, $transaction, $amountOwed, $recipientCharacterId, string $type)
    {
        // Send a receipt.
        /* @var Template $template */
        $template = Template::where('name', 'receipt')->first();

        // Replace placeholder elements in email template.
        $template->subject = str_replace('{date}', date('Y-m-d'), $template->subject);
        $template->subject = str_replace('{name}', $name, $template->subject);
        $template->subject = str_replace('{amount}', $transaction->amount, $template->subject);
        $template->subject = str_replace('{amount_owed}', number_format($amountOwed), $template->subject);
        $template->body = str_replace('{date}', date('Y-m-d'), $template->body);
        $template->body = str_replace('{name}', $name, $template->body);
        $template->body = str_replace('{amount}', $transaction->amount, $template->body);
        $template->body = str_replace('{amount_owed}', number_format($amountOwed), $template->body);
        $mail = array(
            'body' => $template->body,
            'recipients' => array(
                array(
                    'recipient_id' => $recipientCharacterId,
                    'recipient_type' => 'character'
                )
            ),
            'subject' => $template->subject,
        );

        // Queue sending the eve mail, spaced at 1 minute intervals to avoid triggering the mail spam limiter (4/min).
        SendEvemail::dispatch($mail)->delay(Carbon::now()->addMinutes($this->delay_counter));
        $this->delay_counter++;
        Log::info("PollWallet: queued job to send $type receipt eve mail in " . $this->delay_counter . ' minutes');
    }
}
