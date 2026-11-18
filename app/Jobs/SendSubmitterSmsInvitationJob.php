<?php

namespace App\Jobs;

use App\Models\Submitter;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSubmitterSmsInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly int $submitterId,
        public readonly string $senderName,
        public readonly string $templateName,
    ) {}

    public function handle(SmsService $sms): void
    {
        $submitter = Submitter::findOrFail($this->submitterId);

        if (! $submitter->phone) {
            Log::warning("Submitter {$this->submitterId} has no phone number.");

            return;
        }

        $url = url("/s/{$submitter->slug}");
        $body = "{$this->senderName} has sent you a document to sign: \"{$this->templateName}\". Sign here: {$url}";

        $sms->send($submitter->phone, $body);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SMS invitation failed for submitter {$this->submitterId}: {$e->getMessage()}");
    }
}
