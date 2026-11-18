<?php

namespace Database\Factories;

use App\Models\DocumentGenerationEvent;
use App\Models\Submitter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentGenerationEvent>
 */
class DocumentGenerationEventFactory extends Factory
{
    protected $model = DocumentGenerationEvent::class;

    public function definition(): array
    {
        return [
            'submitter_id' => Submitter::factory(),
            'event_name' => $this->faker->randomElement(['start', 'complete']),
        ];
    }
}
