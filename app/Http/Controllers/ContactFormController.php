<?php

namespace App\Http\Controllers;

use App\Jobs\PostSlackMessage;
use App\Jobs\SendEvemail;
use App\Models\Whitelist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ContactFormController extends Controller
{

    public function index()
    {
        return view('contact-form');
    }

    public function send(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return view('/contact-form');
        }

        $text =
            "FROM: " . $user->name . "\n" .
            "TEXT: \n" .
            $request->post('text');

        $this->sendMail($text);
        $this->postSlack($text);

        return view('/contact-form-result');
    }

    /**
     * @param string $text
     */
    private function sendMail($text)
    {
        $recipients = [];
        foreach (Whitelist::where('form_mail', 1)->get() as $recipient) {
            $recipients[] = [
                'recipient_id' => $recipient->eve_id,
                'recipient_type' => 'character'
            ];
        }

        if (count($recipients) === 0) {
            Log::error('ContactFormController: no recipients found.');
            return;
        }

        $mail = array(
            'body' => $text,
            'recipients' => $recipients,
            'subject' => 'Contact Form',
            'approved_cost' => 0,
        );

        SendEvemail::dispatch($mail);
    }

    /**
     * @param string $text
     */
    private function postSlack($text)
    {
        $webHookUrl = config('eve.slack_webhook_url');
        if ($webHookUrl === '') {
            Log::warning('ContactFormController: SLACK_WEBHOOK_URL not set.');
            return;
        }

        PostSlackMessage::dispatch($webHookUrl, ['text' => "*Contact Form*\n\n" . $text]);
    }
}
