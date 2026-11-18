<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Template;
use Illuminate\Http\Request;

class SubmissionsPerTemplateExportController extends Controller
{
    public function index(Request $request, int $templateId)
    {
        $user = $request->user();
        $template = Template::forAccount($user->account_id)->findOrFail($templateId);

        $submissions = Submission::with(['submitters'])
            ->where('template_id', $template->id)
            ->forAccount($user->account_id)
            ->active()
            ->orderBy('id')
            ->get();

        // Collect all unique field names from the template
        $fields = collect($template->fields ?? [])
            ->reject(fn ($f) => in_array($f['type'] ?? '', ['heading', 'signature', 'initials']))
            ->values();

        $headers = [
            'Submission ID', 'Status', 'Created At',
            'Submitter Name', 'Submitter Email', 'Submitter Phone',
            'Completed At', 'Declined At',
        ];

        foreach ($fields as $field) {
            $headers[] = $field['name'] ?? $field['type'] ?? 'Field';
        }

        $rows = [];
        foreach ($submissions as $submission) {
            foreach ($submission->submitters as $submitter) {
                $values = $submitter->values ?? [];
                $row = [
                    $submission->id,
                    $submission->status,
                    $submission->created_at?->format('Y-m-d H:i:s'),
                    $submitter->name,
                    $submitter->email,
                    $submitter->phone,
                    $submitter->completed_at?->format('Y-m-d H:i:s'),
                    $submitter->declined_at?->format('Y-m-d H:i:s'),
                ];

                foreach ($fields as $field) {
                    $uuid = $field['uuid'] ?? null;
                    $value = $uuid ? ($values[$uuid] ?? '') : '';

                    if (is_bool($value)) {
                        $value = $value ? 'Yes' : 'No';
                    } elseif (is_array($value)) {
                        $value = implode(', ', $value);
                    } elseif (is_string($value) && str_starts_with($value, 'data:')) {
                        $value = '[File]';
                    }

                    $row[] = $value ?? '';
                }

                $rows[] = $row;
            }
        }

        $filename = 'submissions-'.str_replace(' ', '-', strtolower($template->name)).'.csv';

        $callback = function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
