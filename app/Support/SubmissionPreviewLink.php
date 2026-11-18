<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Submission;
use Illuminate\Support\Facades\URL;

/** Grant a completed-submission preview for at most 40 minutes after signing. */
final class SubmissionPreviewLink
{
    public static function for(Submission $submission): string
    {
        $lastCompleted = $submission->submitters()
            ->whereNotNull('completed_at')
            ->max('completed_at');
        if (! $lastCompleted) {
            throw new \LogicException('Only completed submissions can be previewed externally.');
        }
        $expiresAt = \Illuminate\Support\Carbon::parse($lastCompleted)->addMinutes(40);
        if ($expiresAt->isPast()) {
            throw new \LogicException('The public preview window has closed.');
        }
        if ($submission->submitters()->whereNull('completed_at')->exists() ||
            ! $submission->submitters()->exists()) {
            throw new \LogicException('All parties must complete before public preview.');
        }

        return URL::temporarySignedRoute('submission-preview.show', $expiresAt, [
            'slug' => $submission->slug,
        ]);
    }
}
