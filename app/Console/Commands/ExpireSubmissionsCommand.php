<?php

namespace App\Console\Commands;

use App\Jobs\DispatchWebhookEventJob;
use App\Models\Submission;
use App\Models\SubmissionEvent;
use Illuminate\Console\Command;

class ExpireSubmissionsCommand extends Command
{
    protected $signature   = 'vouch:expire-submissions';
    protected $description = 'Mark submissions past their expire_at date as expired';

    public function handle(): int
    {
        $overdue = Submission::with('submitters')
            ->whereNotNull('expire_at')
            ->where('expire_at', '<', now())
            ->whereNull('archived_at')
            ->whereHas('submitters', fn ($q) => $q->whereNull('completed_at')->whereNull('declined_at'))
            ->get();

        $count = 0;

        foreach ($overdue as $submission) {
            $pendingSubmitters = $submission->submitters
                ->whereNull('completed_at')
                ->whereNull('declined_at');

            foreach ($pendingSubmitters as $submitter) {
                $submitter->update(['declined_at' => now()]);

                SubmissionEvent::create([
                    'submission_id'   => $submission->id,
                    'submitter_id'    => $submitter->id,
                    'event_type'      => 'submission.expired',
                    'event_timestamp' => now(),
                    'data'            => ['reason' => 'expire_at exceeded'],
                ]);

                DispatchWebhookEventJob::dispatch(
                    'submission.expired',
                    $submission->account_id,
                    $submitter->id,
                    $submission->id,
                );
            }

            $count++;
        }

        $this->info("Processed {$count} expired submission(s).");

        return Command::SUCCESS;
    }
}
