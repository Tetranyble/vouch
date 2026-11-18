<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Submitter;
use Inertia\Inertia;

class SubmitFormDrawSignatureController extends Controller
{
    public function show(string $slug)
    {
        $submitter = Submitter::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('Public/SubmitForm/DrawSignature', [
            'slug' => $slug,
            'submitter' => $submitter,
        ]);
    }
}
