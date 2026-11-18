<?php

namespace App\Jobs;

use App\Mail\SubmitterInvitationMail;
use App\Models\Submitter;
use App\Services\DynamicMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSubmitterInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $submitterId,
        public readonly string $senderName,
        public readonly string $templateName,
    ) {}

    public function handle(): void
    {
        $submitter = Submitter::findOrFail($this->submitterId);

        if (! $submitter->email) {
            return;
        }

        DynamicMailService::configureForAccount($submitter->account_id);

        Mail::to($submitter->email, $submitter->name)
            ->send(new SubmitterInvitationMail($submitter, $this->senderName, $this->templateName));

        $submitter->update(['sent_at' => now()]);
    }
}
