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
        $userId = env('MAIL_USER_ID', 0);
        if ($userId <= 0) {
            Log::error(
                'SendEvemail: cannot send mail to character ' .
                $this->mail['recipients'][0]['recipient_id'] . ', MAIL_USER_ID not set'
            );
            return;
        }

        $esi = new EsiConnection;
        $conn = $esi->getConnection($userId);
        $conn->setBody($this->mail);
        $conn->invoke('post', '/characters/{character_id}/mail/', [
            'character_id' => $userId,
        ]);

        Log::info(
            'SendEvemail: sent evemail to character ' . $this->mail['recipients'][0]['recipient_id'] .
            (count($this->mail['recipients']) > 1 ? ' and ' . (count($this->mail['recipients']) - 1) . ' more.' : '')
        );
    }

    /**
     * Handle failure of sending a mail.
     */
    public function failed(Exception $exception)
    {
        if (!$exception instanceof RequestFailedException) {
            // e.g. EsiScopeAccessDeniedException or something else
            Log::error('SendEvemail: ' . $exception->getMessage());
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
            Log::info(
                'SendEvemail: bounceback due to hitting the error rate limiter, dumping email job to ' .
                ($this->mail['recipients'][0]['recipient_id'] ?? 'none')
            );
            mail(
                env('ADMIN_EMAIL'),
                'Mining Manager rate limiter alert',
                date('Y-m-d H:i:s') .
                ' - SendEvemail: bounceback due to hitting the error rate limiter, dumping email job',
                'From: ' . env('MAIL_FROM_NAME') . ' <' . env('MAIL_FROM_ADDRESS') . '>'
            );
        } elseif (stristr($exception->getEsiResponse()->error, 'ContactCostNotApproved')) {
            // We want to ignore CSPA charge related errors, since they will never send successfully.
            Log::info(
                'SendEvemail: bounceback due to ContactCostNotApproved, dumping email job to ' .
                ($this->mail['recipients'][0]['recipient_id'] ?? 'none')
            );
        } elseif (stristr($exception->getEsiResponse()->error, 'MailStopSpamming')) {
            // If we triggered the anti-spam rate limiter, we want to try again in a few hours.
            $delay = rand(120, 180);
            SendEvemail::dispatch($this->mail)->delay(Carbon::now()->addMinutes($delay));
            Log::info('SendEvemail: bounceback due to MailStopSpamming, re-queued job to send mail in 2-3 hours');
        } elseif (stripos($exception->getEsiResponse()->error, 'ContactOwnerUnreachable') !== false) {
            Log::info(
                'SendEvemail: ContactOwnerUnreachable (receiver blocked sender), dumping email job to ' .
                ($this->mail['recipients'][0]['recipient_id'] ?? 'none')
            );
        } elseif (stripos($exception->getEsiResponse()->error, 'bad recipient') !== false) {
            Log::info(
                'SendEvemail: ' . $exception->getEsiResponse()->error . ', dumping email job. ' .
                'Recipients: ' . json_encode($this->mail['recipients']) . ', ' .
                'Subject: ' . $this->mail['subject']
            );
        } else {
            // Send failed for some other reason (for example downtime), try again in a while.
            SendEvemail::dispatch($this->mail)->delay(Carbon::now()->addMinutes(15));
            Log::info('SendEvemail: re-queued job to send mail in 15 minutes');
        }
    }
}
