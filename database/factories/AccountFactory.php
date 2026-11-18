<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'timezone' => $this->faker->timezone(),
            'locale' => 'en',
            'uuid' => (string) Str::uuid(),
            'archived_at' => null,
        ];
    }
}
