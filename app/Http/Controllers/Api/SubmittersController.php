<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submitter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmittersController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Submitter::where('account_id', $user->account_id)->latest();

        if ($submissionId = $request->get('submission_id')) {
            $query->where('submission_id', $submissionId);
        }

        if ($status = $request->get('status')) {
            match ($status) {
                'completed' => $query->whereNotNull('completed_at'),
                'pending' => $query->whereNull('completed_at')->whereNull('declined_at'),
                'declined' => $query->whereNotNull('declined_at'),
                default => null,
            };
        }

        $submitters = $query->paginate(min(100, max(1, $request->integer('per_page', 10))));

        return response()->json([
            'data' => $submitters->map(fn ($s) => $this->serialize($s)),
            'pagination' => [
                'count' => $submitters->total(),
                'next' => $submitters->nextPageUrl(),
                'prev' => $submitters->previousPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $submitter = Submitter::where('account_id', $user->account_id)->findOrFail($id);

        return response()->json($this->serialize($submitter, detailed: true));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'values' => 'nullable|array',
        ]);

        $user = $request->user();
        $submitter = Submitter::where('account_id', $user->account_id)->findOrFail($id);
        abort_if($submitter->completed_at || $submitter->declined_at, 422, 'Signed or declined records cannot be edited.');
        $submitter->update($request->only('name', 'email', 'phone', 'values'));

        return response()->json($this->serialize($submitter));
    }

    private function serialize(Submitter $submitter, bool $detailed = false): array
    {
        $data = [
            'id' => $submitter->id,
            'slug' => $submitter->slug,
            'uuid' => $submitter->uuid,
            'name' => $submitter->name,
            'email' => $submitter->email,
            'phone' => $submitter->phone,
            'status' => $submitter->status,
            'completed_at' => $submitter->completed_at,
            'declined_at' => $submitter->declined_at,
            'sent_at' => $submitter->sent_at,
            'opened_at' => $submitter->opened_at,
            'submission_id' => $submitter->submission_id,
        ];

        if ($detailed) {
            $data['values'] = $submitter->values ?? [];
        }

        return $data;
    }
}
