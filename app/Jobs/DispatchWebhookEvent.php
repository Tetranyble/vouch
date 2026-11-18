<?php

namespace App\Jobs;

use App\Services\WebhookDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchWebhookEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $event,
        private readonly array $payload
    ) {
    }

    public function handle(WebhookDeliveryService $webhookDeliveryService): void
    {
        $webhookDeliveryService->dispatch($this->event, $this->payload);
    }
}
