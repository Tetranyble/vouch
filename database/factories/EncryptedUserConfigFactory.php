<?php

namespace Database\Factories;

use App\Models\EncryptedUserConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EncryptedUserConfig>
 */
class EncryptedUserConfigFactory extends Factory
{
    protected $model = EncryptedUserConfig::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => $this->faker->unique()->word(),
            'value' => ['secret' => $this->faker->password()],
        ];
    }
}
