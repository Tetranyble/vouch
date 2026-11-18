<?php

namespace App\Actions\Submitters;

use App\Jobs\SendSubmitterInvitationJob;
use App\Jobs\SendSubmitterSmsInvitationJob;
use App\Models\Submitter;

class InviteSubmitterAction
{
    public function execute(Submitter $submitter, string $senderName, string $templateName): void
    {
        if ($submitter->email) {
            SendSubmitterInvitationJob::dispatch($submitter->id, $senderName, $templateName);
        } elseif ($submitter->phone) {
            SendSubmitterSmsInvitationJob::dispatch($submitter->id, $senderName, $templateName);
        }
    }
}
