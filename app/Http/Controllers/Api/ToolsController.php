<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PdfMergeService;
use App\Services\PdfSignatureVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToolsController extends Controller
{
    public function merge(Request $request, PdfMergeService $service): JsonResponse
    {
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'required|string',
        ]);

        $pdfContents = array_map(
            fn (string $b64) => base64_decode($b64, strict: true),
            $request->input('files')
        );

        foreach ($pdfContents as $i => $content) {
            if ($content === false || !str_starts_with($content, '%PDF')) {
                return response()->json(['error' => "File at index {$i} is not a valid base64-encoded PDF"], 422);
            }
        }

        try {
            $merged = $service->merge($pdfContents);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'PDF merge failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['data' => base64_encode($merged)]);
    }

    public function verify(Request $request, PdfSignatureVerificationService $service): JsonResponse
    {
        $request->validate([
            'file' => 'required|string',
        ]);

        $content = base64_decode($request->input('file'), strict: true);

        if ($content === false || !str_starts_with($content, '%PDF')) {
            return response()->json(['error' => 'Invalid base64-encoded PDF'], 422);
        }

        $result = $service->verify($content);

        return response()->json($result);
    }
}
