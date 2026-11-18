<?php

namespace App\Http\Controllers\Public;

use App\Facades\DocumentStorage;
use App\Http\Controllers\Controller;
use App\Models\CompletedDocument;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionPreviewController extends Controller
{
    // A public view requires BOTH an expiring signed URL and a recent completion.
    // A submission slug alone is not authorization.
    private const PUBLIC_TTL_MINUTES = 40;

    public function show(Request $request, string $slug): Response
    {
        $submission = Submission::where('slug', $slug)
            ->with(['submitters.completedDocuments', 'template', 'createdByUser'])
            ->firstOrFail();

        abort_unless($submission->account && ! $submission->account->archived_at, 404);

        $allCompleted = $submission->submitters->isNotEmpty()
            && $submission->submitters->every(fn ($s) => $s->completed_at !== null);

        // Access rules:
        // Account owners may view. External recipients need a valid signed link
        // issued after completion and within the short preview window.
        if ($submission->account?->archived_at || ! $this->canView($request, $submission, $allCompleted)) {
            abort(404);
        }

        $submitters = $submission->submitters->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'email' => $s->email,
            'completed_at' => $s->completed_at,
            'declined_at' => $s->declined_at,
            'status' => $s->status,
        ])->values()->all();

        $documents = $this->buildDocumentList($submission);

        return Inertia::render('Public/SubmissionPreview', [
            'submission' => [
                'id' => $submission->id,
                'slug' => $submission->slug,
                'name' => $submission->name ?? $submission->template?->name,
                'status' => $submission->status,
                'created_at' => $submission->created_at,
            ],
            'submitters' => $submitters,
            'documents' => $documents,
        ]);
    }

    private function canView(Request $request, Submission $submission, bool $allCompleted): bool
    {
        // Authenticated user owns this submission's account
        $user = $request->user();
        if ($user && $user->account_id === $submission->account_id) {
            return true;
        }

        if (! $allCompleted || ! $request->hasValidSignature()) {
            return false;
        }

        // Within public TTL after last signature
        $lastCompleted = $submission->submitters
            ->filter(fn ($s) => $s->completed_at)
            ->max('completed_at');

        return $lastCompleted && $lastCompleted->gt(now()->subMinutes(self::PUBLIC_TTL_MINUTES));
    }

    private function buildDocumentList(Submission $submission): array
    {
        $docs = [];

        foreach ($submission->submitters as $submitter) {
            $combined = $submitter->completedDocuments->firstWhere('filename', 'combined.pdf');
            $selected = $combined && $combined->storage_path
                ? collect([$combined])
                : $submitter->completedDocuments->where('filename', '!=', 'combined.pdf');
            foreach ($selected as $doc) {
                if ($doc->filename === 'audit_trail.pdf' || ! $doc->storage_path) {
                    continue;
                }
                $docs[] = [
                    'id' => $doc->id,
                    'filename' => $doc->filename,
                    'download_url' => URL::temporarySignedRoute(
                        'submission-preview.download',
                        now()->addMinutes(self::PUBLIC_TTL_MINUTES),
                        ['slug' => $submission->slug, 'doc_id' => $doc->id]
                    ),
                ];
            }
        }

        return $docs;
    }

    public function download(Request $request, string $slug, int $docId)
    {
        $submission = Submission::where('slug', $slug)
            ->with(['submitters.completedDocuments'])
            ->firstOrFail();

        $allCompleted = $submission->submitters->isNotEmpty()
            && $submission->submitters->every(fn ($s) => $s->completed_at !== null);

        if (! $submission->account || $submission->account->archived_at ||
            ! $this->canView($request, $submission, $allCompleted)) {
            abort(404);
        }

        $doc = CompletedDocument::whereIn(
            'submitter_id',
            $submission->submitters->pluck('id')
        )->findOrFail($docId);

        if (! $doc->storage_path || ! DocumentStorage::exists($doc->storage_path, $submission->account_id)) {
            abort(404);
        }

        return DocumentStorage::download($doc->storage_path, $submission->account_id, $doc->filename ?? 'document.pdf');
    }
}
