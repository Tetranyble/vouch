<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Submitter;
use Illuminate\Support\Facades\URL;

final class SubmitterDownloadLink
{
    public static function for(Submitter $submitter): string
    {
        return URL::temporarySignedRoute('submit-form.download.index', now()->addDays(7), [
            'slug' => $submitter->slug,
        ]);
    }
}
