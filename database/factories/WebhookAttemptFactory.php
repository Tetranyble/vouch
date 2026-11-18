<?php

namespace Database\Factories;

use App\Models\WebhookAttempt;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookAttempt>
 */
class WebhookAttemptFactory extends Factory
{
    protected $model = WebhookAttempt::class;

    public function definition(): array
    {
        return [
            'webhook_event_id' => WebhookEvent::factory(),
            'response_body' => $this->faker->sentence(),
            'response_status_code' => 200,
            'attempt' => 1,
        ];
    }
}
