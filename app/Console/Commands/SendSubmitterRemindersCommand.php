<?php

namespace App\Console\Commands;

use App\Jobs\SendSubmitterInvitationJob;
use App\Models\AccountConfig;
use App\Models\Submitter;
use Illuminate\Console\Command;

class SendSubmitterRemindersCommand extends Command
{
    protected $signature = 'vouch:send-reminders';

    protected $description = 'Send reminder emails to submitters who have not completed their form, respecting per-account notification settings';

    public function handle(): int
    {
        // Load all accounts that have reminders enabled
        $accountSettings = AccountConfig::whereIn('key', ['send_reminder_email', 'reminder_after_days'])
            ->get()
            ->groupBy('account_id');

        // Accounts with reminders explicitly disabled — skip them
        // Accounts with no config at all — use default (enabled, 3 days)
        $allAccountIds = Submitter::query()
            ->whereNotNull('sent_at')
            ->whereNull('completed_at')
            ->whereNull('declined_at')
            ->whereNotNull('email')
            ->distinct()
            ->pluck('account_id');

        $total = 0;

        foreach ($allAccountIds as $accountId) {
            $settings = $accountSettings->get($accountId, collect())->pluck('value', 'key');

            // Skip if reminders explicitly disabled for this account
            $remindersEnabled = $settings->get('send_reminder_email');
            if ($remindersEnabled !== null && ! filter_var($remindersEnabled, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $days = (int) ($settings->get('reminder_after_days') ?: 3);
            $cutoff = now()->subDays($days);

            $submitters = Submitter::query()
                ->where('account_id', $accountId)
                ->whereNotNull('sent_at')
                ->whereNull('completed_at')
                ->whereNull('declined_at')
                ->whereNotNull('email')
                ->where('sent_at', '<=', $cutoff)
                ->whereHas('submission', fn ($q) => $q->active())
                ->with(['submission.template', 'submission.createdByUser'])
                ->get();

            foreach ($submitters as $submitter) {
                $submission = $submitter->submission;
                $templateName = $submission->template?->name ?? 'Document';
                $senderName = $submission->createdByUser?->full_name
                    ?: ($submission->createdByUser?->email ?? 'Vouch');

                SendSubmitterInvitationJob::dispatch($submitter->id, $senderName, $templateName);
                $total++;

                $this->line("  Reminder queued for {$submitter->email} (account {$accountId}, {$days}d)");
            }
        }

        $this->info("Done. {$total} reminder(s) queued.");

        return Command::SUCCESS;
    }
}
