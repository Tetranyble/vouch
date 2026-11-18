<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateArchivedSubmissionsController extends Controller
{
    public function index(Request $request, string $templateId)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($templateId);

        $submissions = $template->submissions()
            ->with(['submitters', 'createdByUser'])
            ->archived()
            ->latest()
            ->paginate(20)
            ->through(fn ($s) => $this->serialize($s));

        return Inertia::render('Admin/Templates/ArchivedSubmissions', [
            'template' => ['id' => $template->id, 'name' => $template->name],
            'submissions' => $submissions,
        ]);
    }

    public function restore(Request $request, string $templateId, string $submissionId)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($templateId);
        $submission = $template->submissions()->archived()->findOrFail($submissionId);

        $submission->update(['archived_at' => null]);

        return redirect()->back()->with('success', 'Submission restored.');
    }

    public function destroy(Request $request, string $templateId, string $submissionId)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($templateId);
        $submission = $template->submissions()->archived()->findOrFail($submissionId);

        if ($request->boolean('permanently')) {
            $submission->delete();
            $message = 'Submission permanently deleted.';
        } else {
            // Already archived — permanent delete is the only sensible action from archived view
            $submission->delete();
            $message = 'Submission deleted.';
        }

        return redirect()->back()->with('success', $message);
    }

    private function serialize(Submission $submission): array
    {
        return [
            'id' => $submission->id,
            'status' => $submission->status,
            'archived_at' => $submission->archived_at,
            'created_at' => $submission->created_at,
            'submitters' => $submission->submitters->map(fn ($s) => [
                'id' => $s->id,
                'email' => $s->email,
                'name' => $s->name,
                'display_name' => $s->display_name,
                'status' => $s->status,
                'completed_at' => $s->completed_at,
            ])->toArray(),
            'created_by_user' => $submission->createdByUser ? [
                'name' => $submission->createdByUser->full_name ?: $submission->createdByUser->email,
            ] : null,
        ];
    }
}
