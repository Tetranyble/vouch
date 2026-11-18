<?php

namespace App\Services;

use App\Models\CompletedDocument;
use App\Support\TemporaryDocumentFile;

class PdfSignatureVerificationService
{
    public function verify(string $pdfContent): array
    {
        $checksumStatus = $this->verifyChecksum($pdfContent);
        $signatures     = $this->extractSignatures($pdfContent);

        return [
            'checksum_status' => $checksumStatus,
            'signatures'      => $signatures,
        ];
    }

    private function verifyChecksum(string $pdfContent): string
    {
        $hex = hash('sha256', $pdfContent);

        $exists = CompletedDocument::where('sha256', $hex)->exists();

        if ($exists) {
            return 'verified';
        }

        // Also check base64url-encoded SHA256 (used by the original Rails implementation)
        $base64url = rtrim(strtr(base64_encode(hex2bin($hex)), '+/', '-_'), '=');
        $existsB64 = CompletedDocument::where('sha256', $base64url)->exists();

        return $existsB64 ? 'verified' : 'not_found';
    }

    private function extractSignatures(string $pdfContent): array
    {
        $signatures = [];

        // Find all signature dictionaries in the PDF binary
        $offset = 0;
        while (($pos = strpos($pdfContent, '/Type /Sig', $offset)) !== false) {
            $offset = $pos + 1;
            $sigBlock = substr($pdfContent, max(0, $pos - 200), 2000);

            $name    = $this->extractPdfValue($sigBlock, '/Name');
            $reason  = $this->extractPdfValue($sigBlock, '/Reason');
            $location = $this->extractPdfValue($sigBlock, '/Location');
            $date    = $this->extractPdfDate($sigBlock);

            // Extract PKCS#7 Contents and ByteRange for cryptographic verification
            $verified = false;
            $contentsMatch = preg_match('/\/Contents\s*<([0-9a-fA-F]+)>/', $sigBlock, $cm);
            $byteRangeMatch = preg_match('/\/ByteRange\s*\[([^\]]+)\]/', $sigBlock, $brm);

            if ($contentsMatch && $byteRangeMatch) {
                $verified = $this->verifyCmsSignature(
                    $pdfContent,
                    hex2bin($cm[1]),
                    $brm[1]
                );
            }

            $signatures[] = array_filter([
                'name'     => $name,
                'reason'   => $reason,
                'location' => $location,
                'signed_at' => $date,
                'verified' => $verified,
            ], fn ($v) => $v !== null && $v !== '');
        }

        return $signatures;
    }

    private function extractPdfValue(string $block, string $key): ?string
    {
        // Match /Key (value) or /Key value
        if (preg_match('/' . preg_quote($key, '/') . '\s*\(([^)]*)\)/', $block, $m)) {
            return $this->decodePdfString($m[1]);
        }
        if (preg_match('/' . preg_quote($key, '/') . '\s+\/([^\s\/\]>]+)/', $block, $m)) {
            return $m[1];
        }
        return null;
    }

    private function extractPdfDate(string $block): ?string
    {
        if (!preg_match('/\/M\s*\(D:(\d{14})/', $block, $m)) {
            return null;
        }
        $raw = $m[1];
        $year   = substr($raw, 0, 4);
        $month  = substr($raw, 4, 2);
        $day    = substr($raw, 6, 2);
        $hour   = substr($raw, 8, 2);
        $minute = substr($raw, 10, 2);
        $second = substr($raw, 12, 2);

        return "{$year}-{$month}-{$day}T{$hour}:{$minute}:{$second}Z";
    }

    private function decodePdfString(string $str): string
    {
        return preg_replace_callback('/\\\\([0-7]{3}|n|r|t|\\\\|\()/', function ($m) {
            $c = $m[1];
            if (strlen($c) === 3) {
                return chr(octdec($c));
            }
            return match ($c) {
                'n'  => "\n",
                'r'  => "\r",
                't'  => "\t",
                '\\' => '\\',
                '('  => '(',
                default => $c,
            };
        }, $str);
    }

    private function verifyCmsSignature(string $pdfContent, string $pkcs7Der, string $byteRangeStr): bool
    {
        $parts = preg_split('/\s+/', trim($byteRangeStr));
        if (count($parts) < 4) {
            return false;
        }

        [$o1, $l1, $o2, $l2] = array_map('intval', $parts);

        $signed = substr($pdfContent, $o1, $l1) . substr($pdfContent, $o2, $l2);

        $tmpData = null;
        $tmpSig = null;
        try {
            $tmpData = TemporaryDocumentFile::create($signed, 'pdf_sig_data_');
            $tmpSig = TemporaryDocumentFile::create($pkcs7Der, 'pdf_sig_cms_');
            $result = openssl_pkcs7_verify(
                $tmpData,
                PKCS7_BINARY | PKCS7_NOVERIFY,
                '/dev/null',
                [],
                $tmpSig
            );
            return $result === true;
        } catch (\Throwable) {
            return false;
        } finally {
            if ($tmpData) @unlink($tmpData);
            if ($tmpSig) @unlink($tmpSig);
        }
    }
}
