<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\WebhookUrl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookUrl>
 */
class WebhookUrlFactory extends Factory
{
    protected $model = WebhookUrl::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'url' => $this->faker->url(),
            'events' => ['submission.completed', 'submission.invited'],
            'sha1' => $this->faker->sha1(),
            'secret' => $this->faker->sha1(),
        ];
    }
}
