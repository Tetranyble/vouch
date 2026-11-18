<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\SearchEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchEntry>
 */
class SearchEntryFactory extends Factory
{
    protected $model = SearchEntry::class;

    public function definition(): array
    {
        return [
            'record_type' => 'Template',
            'record_id' => 1,
            'account_id' => Account::factory(),
            'tsvector' => $this->faker->sentence(),
            'ngram' => $this->faker->sentence(),
        ];
    }
}
