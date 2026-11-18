<?php

namespace App\Http\Controllers\Admin;

use App\Facades\DocumentStorage;
use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Template;
use App\Support\TemporaryDocumentFile;
use Illuminate\Http\Request;
use setasign\Fpdi\Tcpdf\Fpdi;

class SubmissionsPreviewController extends Controller
{
    public function show(Request $request, int $submission)
    {
        $user = $request->user();
        $submissionModel = Submission::with(['submitters.completedDocuments', 'template'])
            ->forAccount($user->account_id)
            ->findOrFail($submission);

        // Collect all non-audit completed docs across all submitters
        $storagePaths = collect();
        foreach ($submissionModel->submitters as $submitter) {
            foreach ($submitter->completedDocuments as $doc) {
                if (! in_array($doc->filename, ['audit_trail.pdf', 'combined.pdf'], true) && $doc->storage_path && DocumentStorage::exists($doc->storage_path, $user->account_id)) {
                    $storagePaths->push($doc->storage_path);
                }
            }
        }

        // A legacy/completed submission can have only a combined artifact.
        if ($storagePaths->isEmpty()) {
            foreach ($submissionModel->submitters as $submitter) {
                $combined = $submitter->completedDocuments->firstWhere('filename', 'combined.pdf');
                if ($combined?->storage_path && DocumentStorage::exists($combined->storage_path, $user->account_id)) {
                    $storagePaths->push($combined->storage_path);
                }
            }
        }

        // Fall back to the template's original documents when no completed docs yet
        if ($storagePaths->isEmpty()) {
            // Prefer the immutable submission manifest, not a subsequently
            // edited template. Older submissions retain a compatibility fallback.
            $documents = $submissionModel->template_documents ?? $submissionModel->template?->documents ?? [];
            foreach ($documents as $doc) {
                if (! empty($doc['storage_path']) && DocumentStorage::exists($doc['storage_path'], $user->account_id)) {
                    $storagePaths->push($doc['storage_path']);
                }
            }
        }

        if ($storagePaths->isEmpty()) {
            abort(404, 'No documents available for preview.');
        }

        $pdf = new Fpdi;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($storagePaths as $path) {
            $tmpFile = TemporaryDocumentFile::create(
                DocumentStorage::get($path, $user->account_id), 'ds_prev_', 'pdf'
            );

            try {
                $pageCount = $pdf->setSourceFile($tmpFile);

                for ($i = 1; $i <= $pageCount; $i++) {
                    $tpl = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tpl);

                    $pdf->AddPage(
                        $size['width'] > $size['height'] ? 'L' : 'P',
                        [$size['width'], $size['height']]
                    );
                    $pdf->useImportedPage($tpl, 0, 0, $size['width'], $size['height']);

                    $this->applyWatermark($pdf, $size['width'], $size['height']);
                }
            } finally {
                @unlink($tmpFile);
            }
        }

        $content = $pdf->Output('', 'S');
        $filename = 'preview-submission-'.$submissionModel->id.'.pdf';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store',
        ]);
    }

    private function applyWatermark(Fpdi $pdf, float $w, float $h): void
    {
        $pdf->SetFont('helvetica', 'B', 52);
        $pdf->SetTextColor(200, 200, 200);
        $pdf->SetAlpha(0.35);

        $cx = $w / 2;
        $cy = $h / 2;

        $pdf->StartTransform();
        $pdf->Rotate(45, $cx, $cy);
        $pdf->SetXY($cx - 40, $cy - 10);
        $pdf->Cell(80, 20, 'PREVIEW', 0, 0, 'C');
        $pdf->StopTransform();

        $pdf->SetAlpha(1);
        $pdf->SetTextColor(0, 0, 0);
    }
}
