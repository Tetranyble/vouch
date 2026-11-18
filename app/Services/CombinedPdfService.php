<?php

namespace App\Services;

use App\Models\Submitter;
use App\Facades\DocumentStorage;
use App\Support\TemporaryDocumentFile;
use setasign\Fpdi\Tcpdf\Fpdi;

class CombinedPdfService
{
    public function __construct(private readonly CompletedDocumentWriter $writer) {}
    /**
     * Merge all completed documents for a submitter into a single PDF file.
     * Returns the storage path of the combined file.
     */
    public function combineForSubmitter(Submitter $submitter): ?string
    {
        $docs = $submitter->completedDocuments()
            ->whereNotNull('storage_path')
            ->whereNotIn('filename', ['audit_trail.pdf', 'combined.pdf'])
            ->get();

        if ($docs->count() <= 1) {
            return null; // Nothing to combine
        }

        $submission = $submitter->submission;
        if (! $submission || (int) $submission->account_id !== (int) $submitter->account_id) {
            throw new \RuntimeException('Submission workspace does not match submitter workspace.');
        }

        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($docs as $doc) {
            if (! DocumentStorage::exists($doc->storage_path, $submitter->account_id)) {
                throw new \RuntimeException('Signed document is missing from its workspace.');
            }

            $tmpFile = TemporaryDocumentFile::create(
                DocumentStorage::get($doc->storage_path, $submitter->account_id), 'ds_comb_', 'pdf'
            );

            try {
                $pageCount = $pdf->setSourceFile($tmpFile);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tpl  = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tpl);
                    $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
                    $pdf->useImportedPage($tpl, 0, 0, $size['width'], $size['height']);
                }
            } finally {
                @unlink($tmpFile);
            }
        }

        $content = $pdf->Output('', 'S');
        return $this->writer->write($submitter, 'combined.pdf', null, $content, 'combined-pdf')->storage_path;
    }
}
