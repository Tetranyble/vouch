<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountConfig>
 */
class AccountConfigFactory extends Factory
{
    protected $model = AccountConfig::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'key' => $this->faker->unique()->word(),
            'value' => ['enabled' => true],
        ];
    }
}
