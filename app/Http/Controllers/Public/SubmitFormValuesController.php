<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Submitter;

class SubmitFormValuesController extends Controller
{
    public function index(string $slug)
    {
        $submitter = Submitter::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'slug' => $slug,
            'values' => $submitter->values ?? [],
        ]);
    }
}
