<?php

namespace App\Jobs;

use App\Classes\EsiConnection;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Seat\Eseye\Exceptions\RequestFailedException;

class SendEvemail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    private $mail;

    /**
     * Create a new job instance.
     *
     * @param array $mail
     */
    public function __construct($mail)
    {
        $this->mail = $mail;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $userId = config('eve.mail_user_id');
        if ($userId <= 0) {
            Log::error('SendEvemail: MAIL_USER_ID not set');
            return;
        }

        $esi = new EsiConnection;
        $conn = $esi->getConnection($userId);
        $conn->setBody($this->mail);
        $conn->invoke('post', '/characters/{character_id}/mail/', [
            'character_id' => $userId,
        ]);

        $recipients = array();
        foreach ($this->mail['recipients'] as $recipient) {
            array_push($recipients, $recipient['recipient_id']);
        }

        Log::debug('SendEvemail: sent evemail to character(s)', [ 'mail' => $this->mail ]);
    }

    /**
     * Handle failure of sending a mail.
     */
    public function failed(Exception $exception)
    {
        if (!$exception instanceof RequestFailedException) {
            // e.g. EsiScopeAccessDeniedException or something else
            Log::error('SendEvemail: request failed', ['message' => $exception->getMessage()]);
            return;
        }

        // Check what type of exception was thrown.
        if (
            (
                is_object($exception->getEsiResponse()) && (
                    stristr($exception->getEsiResponse()->error, 'Too many errors') ||
                    stristr($exception->getEsiResponse()->error, 'This software has exceeded the error limit for ESI')
                )
            ) || (
                is_string($exception->getEsiResponse()) && (
                    stristr($exception->getEsiResponse(), 'Too many errors') ||
                    stristr($exception->getEsiResponse(), 'This software has exceeded the error limit for ESI')
                )
            )
        ) {
            // We somehow have triggered the error rate limiter,
            // stop requeueing jobs until we can figure out what broke. :(
            Log::error('SendEvemail: bounceback due to hitting the error rate limiter, dropping job', [
                'char_id' => ($this->mail['recipients'][0]['recipient_id'] ?? 'none'),
            ]
            );
            mail(
                config('eve.admin_email'),
                'Mining Manager rate limiter alert',
                date('Y-m-d H:i:s') .
                ' - SendEvemail: bounceback due to hitting the error rate limiter, dumping email job',
                'From: ' . config('mail.from.name') . ' <' . config('mail.from.address') . '>'
            );
        } elseif (stristr($exception->getEsiResponse()->error, 'ContactCostNotApproved')) {
            // We want to ignore CSPA charge related errors, since they will never send successfully.
            Log::error('SendEvemail: bounceback due to ContactCostNotApproved, dropping job', [
                'char_id'=>($this->mail['recipients'][0]['recipient_id'] ?? 'none'),
            ]);
        } elseif (stristr($exception->getEsiResponse()->error, 'MailStopSpamming')) {
            // If we triggered the anti-spam rate limiter, we want to try again in a few hours.
            $delay = rand(120, 180);
            SendEvemail::dispatch($this->mail)->delay(Carbon::now()->addMinutes($delay));
            Log::error('SendEvemail: bounceback due to MailStopSpamming, re-queued job to send mail in 2-3 hours', [
                'recipient' => $this->mail['recipient'],
                'delay_mins' => $delay,
            ]);
        } elseif (stripos($exception->getEsiResponse()->error, 'ContactOwnerUnreachable') !== false) {
            Log::error('SendEvemail: ContactOwnerUnreachable (receiver blocked sender), dropping job', [
                'char_id' => ($this->mail['recipients'][0]['recipient_id'] ?? 'none'),
            ]);
        } elseif (stripos($exception->getEsiResponse()->error, 'bad recipient') !== false) {
            Log::error('SendEvemail: bad recipient, dumping email job', [
                'recipients' => json_encode($this->mail['recipients']),
                'subject' => $this->mail['subject'],
                'error' => $exception->getEsiResponse()->error,
            ]);
        } else {
            // Send failed for some other reason (for example downtime), try again in a while.
            $delay = 15;
            SendEvemail::dispatch($this->mail)->delay(Carbon::now()->addMinutes($delay));
            Log::info('SendEvemail: re-queued job to send later', [
                'recipient' => $this->mail['recipient'],
                'delay_mins'=> $delay,
            ]);
        }
    }
}
