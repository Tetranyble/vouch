<?php

namespace App\Http\Controllers\Api;

use App\Actions\Submissions\CreateSubmissionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateSubmissionRequest;
use App\Models\Submission;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Submission::with(['submitters', 'template'])
            ->forAccount($user->account_id)
            ->active()
            ->latest();

        if ($status = $request->get('status')) {
            match ($status) {
                'pending' => $query->pending(),
                'completed' => $query->completed(),
                'declined' => $query->declined(),
                default => null,
            };
        }

        if ($templateId = $request->get('template_id')) {
            $query->where('template_id', $templateId);
        }

        $submissions = $query->paginate(min(100, max(1, $request->integer('per_page', 10))));

        return response()->json([
            'data' => $submissions->map(fn ($s) => $this->serialize($s)),
            'pagination' => [
                'count' => $submissions->total(),
                'next' => $submissions->nextPageUrl(),
                'prev' => $submissions->previousPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $submission = Submission::with(['submitters', 'template'])
            ->forAccount($user->account_id)
            ->findOrFail($id);

        return response()->json($this->serialize($submission, detailed: true));
    }

    public function store(CreateSubmissionRequest $request, CreateSubmissionAction $action): JsonResponse
    {
        $user     = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($request->input('template_id'));

        $submission = $action->execute(
            template:        $template,
            creator:         $user,
            submitterInputs: $request->submitterInputs(),
            source:          'api',
            expireAt:        $request->input('expire_at'),
        );

        return response()->json($this->serialize($submission, detailed: true), 201);
    }

    public function storeForTemplate(CreateSubmissionRequest $request, string $templateId, CreateSubmissionAction $action): JsonResponse
    {
        $user     = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($templateId);

        $submission = $action->execute(
            template:        $template,
            creator:         $user,
            submitterInputs: $request->submitterInputs(),
            source:          'api',
            expireAt:        $request->input('expire_at'),
        );

        return response()->json($this->serialize($submission, detailed: true), 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $submission = Submission::forAccount($user->account_id)->findOrFail($id);

        if ($request->boolean('permanently')) {
            $submission->delete();
        } else {
            $submission->update(['archived_at' => now()]);
        }

        return response()->json(['id' => (int) $id]);
    }

    private function serialize(Submission $submission, bool $detailed = false): array
    {
        $data = [
            'id' => $submission->id,
            'slug' => $submission->slug,
            'status' => $submission->status,
            'source' => $submission->source,
            'created_at' => $submission->created_at,
            'updated_at' => $submission->updated_at,
            'template' => $submission->template ? [
                'id' => $submission->template->id,
                'name' => $submission->template->name,
            ] : null,
            'submitters' => $submission->submitters->map(fn ($s) => [
                'id' => $s->id,
                'slug' => $s->slug,
                'name' => $s->name,
                'email' => $s->email,
                'phone' => $s->phone,
                'status' => $s->status,
                'completed_at' => $s->completed_at,
                'declined_at' => $s->declined_at,
                'sent_at' => $s->sent_at,
                'opened_at' => $s->opened_at,
            ])->values()->toArray(),
        ];

        if ($detailed) {
            $data['template_fields'] = $submission->template_fields;
        }

        return $data;
    }
}
