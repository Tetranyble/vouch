<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateFolder>
 */
class TemplateFolderFactory extends Factory
{
    protected $model = TemplateFolder::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'account_id' => Account::factory(),
            'author_id' => fn (array $attributes): int => User::factory()->create([
                'account_id' => $attributes['account_id'],
            ])->id,
            'archived_at' => null,
            'parent_folder_id' => null,
        ];
    }
}
