<?php

namespace Database\Factories;

use App\Models\Template;
use App\Models\TemplateAccess;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateAccess>
 */
class TemplateAccessFactory extends Factory
{
    protected $model = TemplateAccess::class;

    public function definition(): array
    {
        return [
            'template_id' => Template::factory(),
            'user_id' => fn (array $attributes): int => Template::findOrFail($attributes['template_id'])->author_id,
        ];
    }
}
