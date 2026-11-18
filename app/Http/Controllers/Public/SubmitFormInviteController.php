<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitFormInviteRequest;
use App\Models\Submitter;
use App\Services\SubmissionFlowService;

class SubmitFormInviteController extends Controller
{
    public function store(
        SubmitFormInviteRequest $request,
        SubmissionFlowService $submissionFlowService,
        string $slug
    ) {
        $submitter = Submitter::query()
            ->where('slug', $slug)
            ->with('submission')
            ->firstOrFail();

        $submissionFlowService->inviteNext($submitter->submission, $submitter);

        return response()->json([
            'slug' => $slug,
            'status' => 'invited',
        ]);
    }
}
