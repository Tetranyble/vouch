<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Submission;
use App\Models\SubmissionEvent;
use App\Models\Submitter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionEvent>
 */
class SubmissionEventFactory extends Factory
{
    protected $model = SubmissionEvent::class;

    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'submitter_id' => fn (array $attributes): int => Submitter::factory()->create([
                'submission_id' => $attributes['submission_id'],
                'account_id' => Submission::findOrFail($attributes['submission_id'])->account_id,
            ])->id,
            'account_id' => fn (array $attributes): int => Submission::findOrFail($attributes['submission_id'])->account_id,
            'data' => ['status' => 'submitted'],
            'event_type' => 'submit',
            'event_timestamp' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
