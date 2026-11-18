<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionsExportController extends Controller
{
    public function index(Request $request)
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

        $submissions = $query->get();

        // Collect all unique field names across all submissions
        $fieldNames = [];
        foreach ($submissions as $submission) {
            $fields = $submission->template_fields ?? $submission->template?->fields ?? [];
            foreach ($fields as $field) {
                $key = $field['uuid'] ?? null;
                if ($key && ! isset($fieldNames[$key])) {
                    $fieldNames[$key] = $field['name'] ?? $field['type'] ?? $key;
                }
            }
        }

        $filename = 'submissions-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($submissions, $fieldNames) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // Header row
            $header = [
                'Submission ID', 'Template', 'Status', 'Created At',
                'Submitter Name', 'Submitter Email', 'Submitter Status',
                'Sent At', 'Opened At', 'Completed At', 'Declined At',
            ];
            foreach ($fieldNames as $label) {
                $header[] = $label;
            }
            fputcsv($handle, $header);

            foreach ($submissions as $submission) {
                $fields = $submission->template_fields ?? $submission->template?->fields ?? [];
                $fieldMap = [];
                foreach ($fields as $field) {
                    if ($uuid = $field['uuid'] ?? null) {
                        $fieldMap[$uuid] = $field;
                    }
                }

                foreach ($submission->submitters as $submitter) {
                    $values = $submitter->values ?? [];

                    $row = [
                        $submission->id,
                        $submission->template?->name ?? '',
                        $submission->status,
                        $submission->created_at?->format('Y-m-d H:i:s'),
                        $submitter->name ?? '',
                        $submitter->email ?? '',
                        $submitter->status,
                        $submitter->sent_at?->format('Y-m-d H:i:s'),
                        $submitter->opened_at?->format('Y-m-d H:i:s'),
                        $submitter->completed_at?->format('Y-m-d H:i:s'),
                        $submitter->declined_at?->format('Y-m-d H:i:s'),
                    ];

                    foreach ($fieldNames as $uuid => $label) {
                        $val = $values[$uuid] ?? '';
                        $fieldType = $fieldMap[$uuid]['type'] ?? '';
                        if (in_array($fieldType, ['signature', 'initials', 'image', 'stamp'])) {
                            $val = $val ? '[Image]' : '';
                        } elseif (is_array($val)) {
                            $val = implode(', ', $val);
                        } elseif (is_bool($val)) {
                            $val = $val ? 'Yes' : 'No';
                        }
                        $row[] = (string) $val;
                    }

                    fputcsv($handle, $row);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
