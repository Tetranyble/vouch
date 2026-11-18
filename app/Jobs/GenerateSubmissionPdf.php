<?php

namespace App\Jobs;

use App\Services\PdfGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSubmissionPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly string $submissionId)
    {
    }

    public function handle(PdfGenerationService $pdfGenerationService): void
    {
        $pdfGenerationService->generateForSubmission($this->submissionId);
    }
}
