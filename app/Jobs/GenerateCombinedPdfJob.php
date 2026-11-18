<?php

namespace App\Jobs;

use App\Models\Submitter;
use App\Services\CombinedPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateCombinedPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $submitterId) {}

    public function handle(CombinedPdfService $service): void
    {
        $submitter = Submitter::with(['completedDocuments', 'submission'])->findOrFail($this->submitterId);

        $service->combineForSubmitter($submitter);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("Combined PDF generation failed for submitter {$this->submitterId}: {$e->getMessage()}");
    }
}
