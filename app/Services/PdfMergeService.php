<?php

namespace App\Services;

use App\Support\TemporaryDocumentFile;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfMergeService
{
    public function merge(array $pdfContents): string
    {
        $pdf = new Fpdi;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($pdfContents as $content) {
            $tmpFile = TemporaryDocumentFile::create($content, 'pdf_merge_', 'pdf');

            try {
                $pageCount = $pdf->setSourceFile($tmpFile);

                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplId = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tplId);

                    $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplId);
                }
            } finally {
                @unlink($tmpFile);
            }
        }

        return $pdf->Output('', 'S');
    }
}
