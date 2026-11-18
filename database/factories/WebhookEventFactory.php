<?php

namespace Database\Factories;

use App\Models\WebhookEvent;
use App\Models\WebhookUrl;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'webhook_url_id' => WebhookUrl::factory(),
            'account_id' => fn (array $attributes): int => WebhookUrl::findOrFail($attributes['webhook_url_id'])->account_id,
            'record_id' => 1,
            'record_type' => 'Submission',
            'event_type' => 'submission.completed',
            'status' => $this->faker->randomElement(['pending', 'delivered', 'error']),
        ];
    }
}
