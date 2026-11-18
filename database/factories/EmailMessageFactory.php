<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\EmailMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailMessage>
 */
class EmailMessageFactory extends Factory
{
    protected $model = EmailMessage::class;

    public function definition(): array
    {
        $subject = $this->faker->sentence(6);
        $body = $this->faker->paragraph();

        return [
            'uuid' => (string) Str::uuid(),
            'account_id' => Account::factory(),
            'author_id' => fn (array $attributes): int => User::factory()->create([
                'account_id' => $attributes['account_id'],
            ])->id,
            'subject' => $subject,
            'body' => $body,
            'sha1' => sha1($subject.$body),
        ];
    }
}
