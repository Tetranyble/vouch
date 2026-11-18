<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PdfSignatureVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerifyPdfSignatureController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Admin/VerifySignature');
    }

    public function store(
        Request $request,
        PdfSignatureVerificationService $service
    ): JsonResponse {
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'required|file|mimetypes:application/pdf',
        ]);

        $results = [];

        foreach ($request->file('files') as $file) {
            $content = file_get_contents($file->getRealPath());
            $result  = $service->verify($content);

            $results[] = array_merge($result, [
                'name' => $file->getClientOriginalName(),
            ]);
        }

        return response()->json($results);
    }
}
