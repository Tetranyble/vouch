<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private string $accountSid;

    private string $authToken;

    private string $from;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid', '');
        $this->authToken = config('services.twilio.auth_token', '');
        $this->from = config('services.twilio.from', '');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->accountSid) && ! empty($this->authToken) && ! empty($this->from);
    }

    public function send(string $to, string $body): bool
    {
        if (! $this->isConfigured()) {
            Log::warning("SMS not configured — cannot send to {$to}");

            return false;
        }

        try {
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'From' => $this->from,
                    'To' => $to,
                    'Body' => $body,
                ]);

            if ($response->failed()) {
                Log::error("Twilio SMS failed to {$to}: ".$response->body());

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Twilio SMS exception: {$e->getMessage()}");

            return false;
        }
    }
}
