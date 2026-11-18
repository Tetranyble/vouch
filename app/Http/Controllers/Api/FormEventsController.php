<?php

namespace App\Http\Controllers\Api;

use App\Models\SubmissionEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormEventsController extends ApiBaseController
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = SubmissionEvent::where('account_id', $user->account_id)
            ->with(['submission.template', 'submitter'])
            ->latest('event_timestamp');

        if ($type = $request->query('event_type')) {
            $query->where('event_type', $type);
        }

        if ($submitterId = $request->query('submitter_id')) {
            $query->where('submitter_id', (int) $submitterId);
        }

        if ($submissionId = $request->query('submission_id')) {
            $query->where('submission_id', (int) $submissionId);
        }

        $result = $this->paginate($query, $request, 25);
        $result['data'] = $result['data']->map(fn ($e) => [
            'id'              => $e->id,
            'event_type'      => $e->event_type,
            'event_timestamp' => $e->event_timestamp,
            'data'            => $e->data ?? [],
            'submission_id'   => $e->submission_id,
            'submitter'       => $e->submitter ? [
                'id'    => $e->submitter->id,
                'email' => $e->submitter->email,
                'name'  => $e->submitter->name,
            ] : null,
            'submission' => $e->submission ? [
                'id'       => $e->submission->id,
                'template' => $e->submission->template ? [
                    'id'   => $e->submission->template->id,
                    'name' => $e->submission->template->name,
                ] : null,
            ] : null,
        ]);

        return response()->json($result);
    }
}
