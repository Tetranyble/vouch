<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Services\DocumentStorage;
use Illuminate\Http\Request;

final class SignedMediaDownloadController extends Controller
{
    public function __invoke(Request $request, int $media, DocumentStorage $storage)
    {
        abort_unless($request->hasValidSignature(), 403);

        // The workspace is included in Laravel's URL signature. Callers cannot
        // substitute a different workspace or look up the owner of an arbitrary ID.
        $account = filter_var($request->query('account'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        abort_unless(is_int($account), 404);

        $reference = 'media:'.$media;
        abort_unless($storage->exists($reference, $account), 404);

        return $storage->download($reference, $account);
    }
}
