<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\EncryptedConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EncryptedConfig>
 */
class EncryptedConfigFactory extends Factory
{
    protected $model = EncryptedConfig::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'key' => $this->faker->unique()->word(),
            'value' => ['secret' => $this->faker->password()],
        ];
    }
}
