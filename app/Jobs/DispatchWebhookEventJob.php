<?php

namespace App\Jobs;

use App\Facades\DocumentStorage;
use App\Models\Submission;
use App\Models\Submitter;
use App\Models\WebhookEvent;
use App\Models\WebhookUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchWebhookEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600, 7200];

    public function __construct(
        public readonly string $eventType,
        public readonly int $accountId,
        public readonly ?int $submitterId = null,
        public readonly ?int $submissionId = null,
    ) {}

    public function handle(): void
    {
        $webhooks = WebhookUrl::where('account_id', $this->accountId)
            ->get()
            ->filter(fn ($w) => empty($w->events) || in_array($this->eventType, $w->events));

        if ($webhooks->isEmpty()) {
            return;
        }

        $payload = $this->buildPayload();

        foreach ($webhooks as $webhook) {
            $this->deliverToWebhook($webhook, $payload);
        }
    }

    private function buildPayload(): array
    {
        $payload = [
            'event_type' => $this->eventType,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($this->submitterId) {
            $submitter = Submitter::with(['submission.template.folder', 'completedDocuments'])->find($this->submitterId);
            if ($submitter && (int) $submitter->account_id === $this->accountId) {
                $payload['data'] = $this->submitterData($submitter);
            }
        } elseif ($this->submissionId) {
            $submission = Submission::with(['submitters', 'template'])->find($this->submissionId);
            if ($submission && (int) $submission->account_id === $this->accountId) {
                $payload['data'] = $this->submissionData($submission);
            }
        }

        return $payload;
    }

    private function submitterData(Submitter $submitter): array
    {
        $submission = $submitter->submission;
        $template = $submission?->template;

        return [
            'id' => $submitter->id,
            'email' => $submitter->email,
            'phone' => $submitter->phone,
            'name' => $submitter->name,
            'ua' => $submitter->ua,
            'ip' => $submitter->ip,
            'sent_at' => $submitter->sent_at?->toIso8601String(),
            'opened_at' => $submitter->opened_at?->toIso8601String(),
            'declined_at' => $submitter->declined_at?->toIso8601String(),
            'completed_at' => $submitter->completed_at?->toIso8601String(),
            'created_at' => $submitter->created_at?->toIso8601String(),
            'updated_at' => $submitter->updated_at?->toIso8601String(),
            'external_id' => $submitter->external_id,
            'metadata' => $submitter->metadata ?? (object) [],
            'status' => $submitter->status,
            'application_key' => null,
            'decline_reason' => null,
            'preferences' => $submitter->preferences ?? (object) [],
            'values' => $this->formatValues($submitter),
            'role' => $this->resolveRole($submitter, $template),
            'documents' => $this->formatDocuments($submitter),
            'template' => $template ? [
                'id' => $template->id,
                'name' => $template->name,
                'external_id' => $template->external_id,
                'created_at' => $template->created_at?->toIso8601String(),
                'updated_at' => $template->updated_at?->toIso8601String(),
                'folder_name' => $template->folder_name,
            ] : null,
            'audit_log_url' => $this->auditLogUrl($submitter),
            'submission_url' => url('/s/'.$submitter->slug),
            'submission' => $submission ? [
                'id' => $submission->id,
                'audit_log_url' => $this->submissionAuditLogUrl($submission),
                'combined_document_url' => null,
                'status' => $submission->status,
                'url' => url('/s/'.$submitter->slug),
                'variables' => $submission->variables ?? (object) [],
                'created_at' => $submission->created_at?->toIso8601String(),
            ] : null,
        ];
    }

    private function submissionData(Submission $submission): array
    {
        $template = $submission->template;

        return [
            'id' => $submission->id,
            'status' => $submission->status,
            'created_at' => $submission->created_at?->toIso8601String(),
            'updated_at' => $submission->updated_at?->toIso8601String(),
            'external_id' => $template?->external_id,
            'variables' => $submission->variables ?? (object) [],
            'template' => $template ? [
                'id' => $template->id,
                'name' => $template->name,
                'external_id' => $template->external_id,
                'created_at' => $template->created_at?->toIso8601String(),
                'updated_at' => $template->updated_at?->toIso8601String(),
                'folder_name' => $template->folder_name,
            ] : null,
            'submitters' => $submission->submitters->map(fn ($s) => [
                'id' => $s->id,
                'email' => $s->email,
                'phone' => $s->phone,
                'name' => $s->name,
                'status' => $s->status,
                'sent_at' => $s->sent_at?->toIso8601String(),
                'completed_at' => $s->completed_at?->toIso8601String(),
                'declined_at' => $s->declined_at?->toIso8601String(),
                'role' => $this->resolveRole($s, $template),
            ])->toArray(),
        ];
    }

    private function formatValues(Submitter $submitter): array
    {
        $values = $submitter->values ?? [];
        $fields = $submitter->submission?->template?->fields ?? [];
        $fieldMap = [];

        foreach ($fields as $field) {
            if ($uuid = $field['uuid'] ?? null) {
                $fieldMap[$uuid] = $field['name'] ?? $field['type'] ?? $uuid;
            }
        }

        $result = [];
        foreach ($values as $uuid => $value) {
            $result[] = [
                'field' => $fieldMap[$uuid] ?? $uuid,
                'value' => is_array($value) ? implode(', ', $value) : (string) ($value ?? ''),
            ];
        }

        return $result;
    }

    private function resolveRole(Submitter $submitter, $template): string
    {
        $submitters = $template?->submitters ?? [];
        $order = (int) ($submitter->order ?? 0);

        return $submitters[$order]['name'] ?? 'Signer';
    }

    private function formatDocuments(Submitter $submitter): array
    {
        return $submitter->completedDocuments
            ->filter(fn ($d) => $d->filename && ! str_contains(strtolower($d->filename), 'audit'))
            ->map(fn ($d) => [
                'name' => pathinfo($d->filename, PATHINFO_FILENAME),
                'url' => DocumentStorage::url($d->storage_path, $this->accountId),
            ])
            ->values()
            ->toArray();
    }

    private function auditLogUrl(Submitter $submitter): ?string
    {
        $doc = $submitter->completedDocuments
            ->first(fn ($d) => str_contains(strtolower($d->filename ?? ''), 'audit'));

        return $doc ? DocumentStorage::url($doc->storage_path, $this->accountId) : null;
    }

    private function submissionAuditLogUrl(Submission $submission): ?string
    {
        // Audit log is the same file shared across submitters — grab from first completed submitter
        foreach ($submission->submitters as $submitter) {
            $doc = $submitter->completedDocuments()
                ->where('filename', 'like', '%audit%')
                ->first();
            if ($doc) {
                return DocumentStorage::url($doc->storage_path, $this->accountId);
            }
        }

        return null;
    }

    private function deliverToWebhook(WebhookUrl $webhook, array $payload): void
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, $webhook->secret ?? '');

        $event = WebhookEvent::create([
            'webhook_url_id' => $webhook->id,
            'event_type' => $this->eventType,
            'status' => 'pending',
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Vouch-Signature' => $signature,
                'User-Agent' => 'Vouch-Webhook/1.0',
            ])
                ->timeout(10)
                ->post($webhook->url, $payload);

            $event->update([
                'status' => $response->successful() ? 'success' : 'error',
                'response_code' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            $event->update(['status' => 'error', 'response_code' => null]);
            Log::warning("Webhook delivery failed for {$webhook->url}: {$e->getMessage()}");
            throw $e;
        }
    }
}
