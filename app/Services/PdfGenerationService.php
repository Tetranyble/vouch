<?php

namespace App\Services;

use App\Facades\DocumentStorage;
use App\Models\Submission;
use App\Models\Submitter;
use App\Support\TemporaryDocumentFile;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfGenerationService
{
    public function __construct(private readonly CompletedDocumentWriter $writer) {}

    private const FONT_SIZE = 11;

    private const CHECK_CHAR = "\xE2\x9C\x94"; // ✔

    public function generateForSubmitter(Submitter $submitter): array
    {
        $submission = $submitter->submission()->with('template')->first();
        if (! $submission) {
            return [];
        }

        $template = $submission->template;
        $schema = $submission->template_schema ?? $template?->schema ?? [];
        $fields = $submission->template_fields ?? $template?->fields ?? [];
        $documents = $submission->template_documents ?? $template?->documents ?? [];

        if (empty($schema) || empty($documents)) {
            return $this->generatePlaceholderDocument($submitter, $submission);
        }

        // Merge values from every submitter so all signatures appear regardless of who triggered generation.
        $submission->loadMissing('submitters');
        $allValues = [];
        foreach ($submission->submitters as $s) {
            $allValues = array_merge($allValues, $s->values ?? []);
        }

        $results = [];
        foreach ($schema as $schemaItem) {
            $attachmentUuid = $schemaItem['attachment_uuid'] ?? null;
            if (! $attachmentUuid) {
                continue;
            }

            $docMeta = collect($documents)->firstWhere('uuid', $attachmentUuid);
            if (! $docMeta || empty($docMeta['storage_path'])) {
                continue;
            }

            if (! DocumentStorage::exists($docMeta['storage_path'], $submission->account_id)) {
                continue;
            }

            $pdf = $this->buildFilledPdf(
                $docMeta['storage_path'],
                $attachmentUuid,
                $fields,
                $allValues,
                $submission->account_id
            );

            $filename = ($schemaItem['name'] ?? $template?->name ?? 'document').'.pdf';
            $completed = $this->writer->write(
                $submitter, $filename, $attachmentUuid, $pdf, 'signed-document'
            );

            $results[] = $completed;
        }

        return $results;
    }

    private function buildFilledPdf(
        string $sourcePath,
        string $attachmentUuid,
        array $fields,
        array $allValues,
        int $accountId
    ): string {
        $tmpSource = TemporaryDocumentFile::create(
            DocumentStorage::get($sourcePath, $accountId), 'ds_src_', 'pdf'
        );

        try {
            $pdf = new Fpdi;
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            $pageCount = $pdf->setSourceFile($tmpSource);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $importedPage = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($importedPage);

                $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', [$size['width'], $size['height']]);
                $pdf->useImportedPage($importedPage, 0, 0, $size['width'], $size['height']);

                $this->drawFieldsOnPage($pdf, $fields, $allValues, $attachmentUuid, $pageNo - 1, $size, $accountId);
            }

            return $pdf->Output('', 'S');
        } finally {
            @unlink($tmpSource);
        }
    }

    private function drawFieldsOnPage(
        Fpdi $pdf,
        array $fields,
        array $values,
        string $attachmentUuid,
        int $pageIndex,
        array $pageSize,
        int $accountId
    ): void {
        $pageW = $pageSize['width'];
        $pageH = $pageSize['height'];

        foreach ($fields as $field) {
            $fieldUuid = $field['uuid'] ?? null;
            $fieldType = $field['type'] ?? 'text';

            $value = $values[$fieldUuid] ?? ($field['default_value'] ?? null);
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $areas = $field['areas'] ?? [];
            foreach ($areas as $area) {
                if (($area['attachment_uuid'] ?? null) !== $attachmentUuid) {
                    continue;
                }
                if (($area['page'] ?? 0) !== $pageIndex) {
                    continue;
                }

                $x = ($area['x'] ?? 0) * $pageW;
                $y = ($area['y'] ?? 0) * $pageH;
                $w = ($area['w'] ?? 0) * $pageW;
                $h = ($area['h'] ?? 0) * $pageH;

                $prefs = $field['preferences'] ?? [];
                $fontSize = isset($prefs['font_size']) ? (int) $prefs['font_size'] : $this->autoFontSize($pageW, self::FONT_SIZE);
                $fillColor = $this->hexToRgb($prefs['color'] ?? null);
                $bgColor = $this->hexToRgb($prefs['background'] ?? null);

                if ($bgColor) {
                    $pdf->SetFillColor(...$bgColor);
                    $pdf->Rect($x, $y, $w, $h, 'F');
                }

                match ($fieldType) {
                    'signature', 'initials', 'stamp', 'image' => $this->drawImage($pdf, $value, $x, $y, $w, $h, $accountId),
                    'checkbox' => $this->drawCheckbox($pdf, $value, $x, $y, $w, $h, $fontSize),
                    'radio', 'multiple' => $this->drawOption($pdf, $value, $field, $area, $x, $y, $w, $h, $fontSize),
                    'date' => $this->drawText($pdf, $this->formatDate($value, $prefs), $x, $y, $w, $h, $fontSize, $prefs, $fillColor),
                    'number' => $this->drawText($pdf, $this->formatNumber($value, $prefs), $x, $y, $w, $h, $fontSize, $prefs, $fillColor),
                    default => $this->drawText($pdf, (string) $value, $x, $y, $w, $h, $fontSize, $prefs, $fillColor),
                };
            }
        }
    }

    private function drawText(Fpdi $pdf, string $text, float $x, float $y, float $w, float $h, int $fontSize, array $prefs, ?array $color): void
    {
        if ($text === '') {
            return;
        }

        $align = strtoupper(substr($prefs['align'] ?? 'L', 0, 1));
        $fontStyle = match ($prefs['font_type'] ?? 'none') {
            'bold' => 'B',
            'italic' => 'I',
            'bold_italic' => 'BI',
            default => '',
        };

        $pdf->SetFont('helvetica', $fontStyle, $fontSize);

        if ($color) {
            $pdf->SetTextColor(...$color);
        } else {
            $pdf->SetTextColor(0, 0, 0);
        }

        // Auto-shrink font if text overflows
        $pdf->SetXY($x, $y);
        while ($fontSize > 4 && $pdf->GetStringWidth($text) > $w) {
            $fontSize--;
            $pdf->SetFont('helvetica', $fontStyle, $fontSize);
        }

        // Multi-line with overflow clipping using a cell
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($w, $h, $text, 0, $align, false, 0, $x, $y, true, 0, false, true, $h, 'M');

        $pdf->SetTextColor(0, 0, 0);
    }

    private function drawImage(Fpdi $pdf, mixed $value, float $x, float $y, float $w, float $h, int $accountId): void
    {
        $tmpFile = null;
        try {
            if (is_string($value) && str_starts_with($value, 'data:image')) {
                if (strlen($value) > 8 * 1024 * 1024) {
                    return;
                }
                $dataUrl = preg_match('/^data:image\/(png|jpeg);base64,([A-Za-z0-9+\/=]+)$/D', $value, $matches)
                    ? $matches : null;
                if (! $dataUrl) {
                    return;
                }
                $data = base64_decode($matches[2], true);
                if ($data === false) {
                    return;
                }
                $tmpFile = TemporaryDocumentFile::create($data, 'ds_img_', $matches[1] === 'png' ? 'png' : 'jpg');
                $imgPath = $tmpFile;
            } elseif (is_string($value) && DocumentStorage::exists($value, $accountId)) {
                $data = DocumentStorage::get($value, $accountId);
                $tmpFile = TemporaryDocumentFile::create($data, 'ds_img_', 'png');
                $imgPath = $tmpFile;
            } else {
                return;
            }

            [$imgW, $imgH] = getimagesize($imgPath);
            if (! $imgW || ! $imgH) {
                return;
            }

            $scale = min($w / $imgW, $h / $imgH);
            $drawW = $imgW * $scale;
            $drawH = $imgH * $scale;
            $drawX = $x + ($w - $drawW) / 2;
            $drawY = $y + ($h - $drawH) / 2;

            $pdf->Image($imgPath, $drawX, $drawY, $drawW, $drawH, '', '', '', false, 300, '', false, false, 0);
        } catch (\Throwable) {
            // Silently skip unrenderable images
        } finally {
            if ($tmpFile && file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    private function drawCheckbox(Fpdi $pdf, mixed $value, float $x, float $y, float $w, float $h, int $fontSize): void
    {
        if ($value !== true && $value !== 'true' && $value !== '1' && $value !== 1) {
            return;
        }

        $pdf->SetFont('zapfdingbats', '', min($fontSize * 1.2, $h * 0.8));
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, chr(52), 0, 0, 'C'); // ✔ in ZapfDingbats
        $pdf->SetFont('helvetica', '', $fontSize);
    }

    private function drawOption(Fpdi $pdf, mixed $value, array $field, array $area, float $x, float $y, float $w, float $h, int $fontSize): void
    {
        $optionUuid = $area['option_uuid'] ?? null;
        if (! $optionUuid) {
            return;
        }

        $option = collect($field['options'] ?? [])->firstWhere('uuid', $optionUuid);
        if (! $option) {
            return;
        }

        $optionValue = $option['value'] ?? '';
        $isSelected = is_array($value)
            ? in_array($optionValue, $value)
            : ($value === $optionValue || $value === true);

        if ($isSelected) {
            $this->drawCheckbox($pdf, true, $x, $y, $w, $h, $fontSize);
        }
    }

    private function formatDate(mixed $value, array $prefs): string
    {
        if (! $value) {
            return '';
        }
        $format = $prefs['format'] ?? 'MM/DD/YYYY';
        try {
            $date = new \DateTime($value);
            $php = str_replace(['MM', 'DD', 'YYYY', 'YY', 'MON'],
                ['m',  'd',  'Y',    'y',  'M'], $format);

            return $date->format($php);
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function formatNumber(mixed $value, array $prefs): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }
        $decimals = (int) ($prefs['decimal_places'] ?? 0);

        return number_format((float) $value, $decimals);
    }

    private function autoFontSize(float $pageWidth, int $base): int
    {
        return (int) (($pageWidth / 595.0) * $base);
    }

    private function hexToRgb(?string $hex): ?array
    {
        if (! $hex) {
            return null;
        }
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return null;
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private function generatePlaceholderDocument(Submitter $submitter, Submission $submission): array
    {
        $pdf = new \TCPDF;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Document Signed', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Ln(5);
        $pdf->Cell(0, 8, 'Template: '.($submission->template?->name ?? 'Unknown'), 0, 1);
        $pdf->Cell(0, 8, 'Signed by: '.($submitter->name ?: $submitter->email ?: 'Unknown'), 0, 1);
        $pdf->Cell(0, 8, 'Signed at: '.now()->format('Y-m-d H:i:s'), 0, 1);

        $values = $submitter->values ?? [];
        if (! empty($values)) {
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Form Values:', 0, 1);
            $pdf->SetFont('helvetica', '', 11);

            $fields = $submission->template_fields ?? $submission->template?->fields ?? [];
            foreach ($fields as $field) {
                $uuid = $field['uuid'] ?? null;
                if (! $uuid || ! isset($values[$uuid])) {
                    continue;
                }

                $label = $field['name'] ?? $uuid;
                $val = is_array($values[$uuid]) ? implode(', ', $values[$uuid]) : $values[$uuid];

                if (in_array($field['type'] ?? '', ['signature', 'initials', 'image', 'stamp'])) {
                    $val = '[Signature/Image]';
                }

                $pdf->Cell(0, 7, "{$label}: {$val}", 0, 1);
            }
        }

        $completed = $this->writer->write(
            $submitter,
            ($submission->template?->name ?? 'document').'.pdf',
            null,
            $pdf->Output('', 'S'),
            'signed-document'
        );

        return [$completed];
    }
}
