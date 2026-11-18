<?php

namespace Database\Factories;

use App\Models\CompletedDocument;
use App\Models\Submitter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompletedDocument>
 */
class CompletedDocumentFactory extends Factory
{
    protected $model = CompletedDocument::class;

    public function definition(): array
    {
        return [
            'submitter_id' => Submitter::factory(),
            'sha256' => $this->faker->sha256(),
        ];
    }
}
