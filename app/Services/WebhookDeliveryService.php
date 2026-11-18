<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\WebhookAttempt;
use App\Models\WebhookEvent;
use App\Models\WebhookUrl;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookDeliveryService
{
    public function dispatch(string $event, array $payload): void
    {
        $submissionId = $payload['submission_id'] ?? null;
        if (!$submissionId) {
            return;
        }

        $submission = Submission::query()->find($submissionId);
        if (!$submission) {
            return;
        }

        $webhooks = WebhookUrl::query()
            ->where('account_id', $submission->account_id)
            ->get();

        foreach ($webhooks as $webhookUrl) {
            $webhookEvent = WebhookEvent::query()->create([
                'uuid' => (string) Str::uuid(),
                'webhook_url_id' => $webhookUrl->id,
                'account_id' => $submission->account_id,
                'record_id' => $submission->id,
                'record_type' => 'Submission',
                'event_type' => $event,
                'status' => 'pending',
            ]);

            $this->deliver($webhookUrl, $webhookEvent, $payload);
        }
    }

    private function deliver(WebhookUrl $webhookUrl, WebhookEvent $webhookEvent, array $payload): void
    {
        $body = json_encode($payload);
        $headers = [
            'X-Vouch-Event' => $webhookEvent->event_type,
        ];

        if (!empty($webhookUrl->secret)) {
            $headers['X-Vouch-Signature'] = hash_hmac('sha256', $body ?: '', $webhookUrl->secret);
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($webhookUrl->url, $payload);

            WebhookAttempt::query()->create([
                'webhook_event_id' => $webhookEvent->id,
                'response_body' => $response->body(),
                'response_status_code' => $response->status(),
                'attempt' => 1,
            ]);

            $webhookEvent->status = $response->successful() ? 'delivered' : 'error';
            $webhookEvent->save();
        } catch (\Throwable $exception) {
            WebhookAttempt::query()->create([
                'webhook_event_id' => $webhookEvent->id,
                'response_body' => $exception->getMessage(),
                'response_status_code' => 0,
                'attempt' => 1,
            ]);

            $webhookEvent->status = 'error';
            $webhookEvent->save();
        }
    }
}
