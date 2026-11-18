<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Template;
use App\Models\TemplateSharing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateSharing>
 */
class TemplateSharingFactory extends Factory
{
    protected $model = TemplateSharing::class;

    public function definition(): array
    {
        return [
            'template_id' => Template::factory(),
            'account_id' => Account::factory(),
            'ability' => $this->faker->randomElement(['read', 'write']),
        ];
    }
}
