<?php

namespace App\Jobs;

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

class SendTestWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly int $webhookUrlId,
        public readonly int $submitterId,
    ) {}

    public function handle(): void
    {
        $webhook = WebhookUrl::find($this->webhookUrlId);

        if (! $webhook) {
            return;
        }

        $payload = $this->buildPayload();

        $this->deliverToWebhook($webhook, $payload);
    }

    private function buildPayload(): array
    {
        $payload = [
            'event_type' => 'form.completed',
            'timestamp' => now()->toIso8601String(),
        ];

        $submitter = Submitter::with('submission.template')->find($this->submitterId);

        if ($submitter) {
            $payload['data'] = [
                'id' => $submitter->id,
                'slug' => $submitter->slug,
                'email' => $submitter->email,
                'name' => $submitter->name,
                'status' => $submitter->status,
                'completed_at' => $submitter->completed_at,
                'declined_at' => $submitter->declined_at,
                'submission' => $submitter->submission ? [
                    'id' => $submitter->submission->id,
                    'status' => $submitter->submission->status,
                    'template' => $submitter->submission->template ? [
                        'id' => $submitter->submission->template->id,
                        'name' => $submitter->submission->template->name,
                    ] : null,
                ] : null,
            ];
        }

        return $payload;
    }

    private function deliverToWebhook(WebhookUrl $webhook, array $payload): void
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, $webhook->secret ?? '');

        $event = WebhookEvent::create([
            'webhook_url_id' => $webhook->id,
            'event_type' => 'form.completed',
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
            $event->update([
                'status' => 'error',
                'response_code' => null,
            ]);

            Log::warning("Test webhook delivery failed for {$webhook->url}: {$e->getMessage()}");
        }
    }
}
