<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\Submitter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Submitter>
 */
class SubmitterFactory extends Factory
{
    protected $model = Submitter::class;

    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'uuid' => (string) Str::uuid(),
            'email' => $this->faker->safeEmail(),
            'slug' => Str::uuid()->toString(),
            'values' => [],
            'ua' => $this->faker->userAgent(),
            'ip' => $this->faker->ipv4(),
            'sent_at' => null,
            'opened_at' => null,
            'completed_at' => null,
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'external_id' => $this->faker->uuid(),
            'preferences' => [],
            'metadata' => [],
            'account_id' => fn (array $attributes): int => Submission::findOrFail($attributes['submission_id'])->account_id,
            'declined_at' => null,
            'timezone' => $this->faker->timezone(),
        ];
    }
}
