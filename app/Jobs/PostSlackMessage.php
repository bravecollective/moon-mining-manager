<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Post a message on Slack, needs a Slack app with an incoming webhook.
 */
class PostSlackMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    private $webHookUrl;

    private $body;

    /**
     * @param string $webHookUrl see https://api.slack.com/incoming-webhooks
     * @param array $body see https://api.slack.com/docs/messages
     */
    public function __construct($webHookUrl, array $body)
    {
        $this->webHookUrl = $webHookUrl;
        $this->body = $body;
    }

    public function handle()
    {
        $guzzleClient = new Client();

        try {
            $guzzleClient->request(
                'POST',
                $this->webHookUrl,
                ['body' => \json_encode($this->body)]
            );
        } catch (GuzzleException $e) {
            Log::error('PostSlackMessage: failed', ['exception_message' => $e->getMessage()]);
            return;
        }

        Log::info('PostSlackMessage: sent');
    }
}
