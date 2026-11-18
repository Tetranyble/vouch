<?php

namespace Database\Factories;

use App\Models\CompletedSubmitter;
use App\Models\Submitter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompletedSubmitter>
 */
class CompletedSubmitterFactory extends Factory
{
    protected $model = CompletedSubmitter::class;

    public function definition(): array
    {
        return [
            'submitter_id' => Submitter::factory(),
            'submission_id' => fn (array $attributes): int => Submitter::findOrFail($attributes['submitter_id'])->submission_id,
            'account_id' => fn (array $attributes): int => Submitter::findOrFail($attributes['submitter_id'])->account_id,
            'template_id' => fn (array $attributes): ?int => Submitter::findOrFail($attributes['submitter_id'])->submission?->template_id,
            'source' => 'email',
            'sms_count' => $this->faker->numberBetween(0, 3),
            'completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'verification_method' => $this->faker->randomElement(['email', 'sms', null]),
            'is_first' => $this->faker->boolean(),
        ];
    }
}
