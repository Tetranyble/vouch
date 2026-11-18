<?php

namespace App\Http\Controllers\Api;

use App\Facades\DocumentStorage;
use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionDocumentsController extends Controller
{
    public function index(Request $request, int $id): JsonResponse
    {
        $accountId = $request->user()->account_id;
        $submission = Submission::with(['submitters.completedDocuments'])
            ->where('account_id', $accountId)
            ->findOrFail($id);

        // Collect completed documents across all submitters (skip audit trail)
        $documents = collect();
        foreach ($submission->submitters as $submitter) {
            foreach ($submitter->completedDocuments as $doc) {
                if ($doc->filename === 'audit_trail.pdf') {
                    continue;
                }
                if (! $doc->storage_path || ! DocumentStorage::exists($doc->storage_path, $accountId)) {
                    continue;
                }

                $documents->push([
                    'name' => pathinfo($doc->filename ?? 'document', PATHINFO_FILENAME),
                    'url' => DocumentStorage::url($doc->storage_path, $accountId),
                ]);
            }
        }

        return response()->json([
            'id' => $submission->id,
            'status' => $submission->status,
            'documents' => $documents->values(),
        ]);
    }
}
