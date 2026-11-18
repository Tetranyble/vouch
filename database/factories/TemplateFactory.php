<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        return [
            'slug' => Str::uuid()->toString(),
            'name' => $this->faker->words(3, true),
            'schema' => [],
            'fields' => [],
            'submitters' => [],
            'account_id' => Account::factory(),
            'author_id' => fn (array $attributes): int => User::factory()->create([
                'account_id' => $attributes['account_id'],
            ])->id,
            'archived_at' => null,
            'source' => 'builder',
            'folder_id' => fn (array $attributes): int => TemplateFolder::factory()->create([
                'account_id' => $attributes['account_id'],
                'author_id' => $attributes['author_id'],
            ])->id,
            'external_id' => null,
            'preferences' => [],
            'shared_link' => false,
            'variables_schema' => [],
        ];
    }
}
