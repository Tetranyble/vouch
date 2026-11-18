<?php

namespace App\Jobs;

use App\Models\SearchEntry;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReindexSearchEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly string $recordType,
        public readonly int $recordId,
    ) {}

    public function handle(): void
    {
        [$model, $accountId, $text] = match ($this->recordType) {
            'Template' => $this->fromTemplate(),
            'Submission' => $this->fromSubmission(),
            'Submitter' => $this->fromSubmitter(),
            default => [null, null, ''],
        };

        if (! $model || ! $accountId || $text === '') {
            return;
        }

        SearchEntry::updateOrCreate(
            ['record_type' => $this->recordType, 'record_id' => $this->recordId],
            ['account_id' => $accountId, 'tsvector' => $text, 'ngram' => $text],
        );
    }

    private function fromTemplate(): array
    {
        $t = Template::find($this->recordId);
        if (! $t) {
            return [null, null, ''];
        }
        $text = trim($t->name);

        return [$t, $t->account_id, $text];
    }

    private function fromSubmission(): array
    {
        $s = Submission::with('submitters')->find($this->recordId);
        if (! $s) {
            return [null, null, ''];
        }
        $parts = array_filter([
            $s->name,
            ...$s->submitters->flatMap(fn ($sub) => [$sub->name, $sub->email, $sub->phone])->filter()->toArray(),
        ]);

        return [$s, $s->account_id, implode(' ', $parts)];
    }

    private function fromSubmitter(): array
    {
        $sub = Submitter::find($this->recordId);
        if (! $sub) {
            return [null, null, ''];
        }
        $text = trim(implode(' ', array_filter([$sub->name, $sub->email, $sub->phone])));

        return [$sub, $sub->account_id, $text];
    }
}
