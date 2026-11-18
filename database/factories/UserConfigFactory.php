<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserConfig>
 */
class UserConfigFactory extends Factory
{
    protected $model = UserConfig::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => $this->faker->unique()->word(),
            'value' => ['enabled' => true],
        ];
    }
}
