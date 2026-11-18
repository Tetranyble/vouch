<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        return [
            'template_id' => Template::factory(),
            'created_by_user_id' => fn (array $attributes): ?int => Template::findOrFail($attributes['template_id'])->author_id,
            'archived_at' => null,
            'template_fields' => [],
            'template_schema' => [],
            'template_submitters' => [],
            'source' => 'builder',
            'submitters_order' => 'preserved',
            'slug' => Str::uuid()->toString(),
            'preferences' => [],
            'account_id' => fn (array $attributes): int => Template::findOrFail($attributes['template_id'])->account_id,
            'expire_at' => null,
            'name' => $this->faker->sentence(3),
            'variables_schema' => [],
            'variables' => [],
        ];
    }
}
