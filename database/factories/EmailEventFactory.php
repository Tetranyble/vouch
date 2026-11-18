<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\EmailEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailEvent>
 */
class EmailEventFactory extends Factory
{
    protected $model = EmailEvent::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'emailable_type' => 'Submission',
            'emailable_id' => 1,
            'message_id' => $this->faker->uuid(),
            'tag' => 'submission_invite',
            'event_type' => 'sent',
            'email' => $this->faker->safeEmail(),
            'data' => ['provider' => 'mailhog'],
            'event_datetime' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
