<?php

namespace App\Jobs;

use App\Services\EmailDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSubmissionEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $submissionId,
        private readonly string $email,
        private readonly string $type
    ) {
    }

    public function handle(EmailDispatchService $emailDispatchService): void
    {
        if ($this->type === 'completed') {
            $emailDispatchService->sendSubmissionCompleted($this->submissionId, $this->email);
            return;
        }

        $emailDispatchService->sendSubmissionInvite($this->submissionId, $this->email);
    }
}
