<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Template;
use App\Models\TemplateFolder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Sample credentials must never be seeded in production.');
        }

        // Create a default account
        $account = Account::firstOrCreate(
            ['name' => 'Default Account'],
            ['locale' => 'en', 'timezone' => 'UTC']
        );

        // Create admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@vouch.test'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'account_id' => $account->id,
                'uuid' => (string) Str::uuid(),
                'email_verified_at' => now(),
            ]
        );

        // Create default folder
        $folder = TemplateFolder::firstOrCreate(
            ['account_id' => $account->id, 'name' => 'Default'],
            ['author_id' => $user->id]
        );

        // Create sample templates
        $templates = [
            [
                'name' => 'Non-Disclosure Agreement',
                'submitters' => [
                    ['name' => 'First Party', 'uuid' => (string) Str::uuid()],
                    ['name' => 'Second Party', 'uuid' => (string) Str::uuid()],
                ],
            ],
            [
                'name' => 'Service Agreement',
                'submitters' => [
                    ['name' => 'Client', 'uuid' => (string) Str::uuid()],
                    ['name' => 'Service Provider', 'uuid' => (string) Str::uuid()],
                ],
            ],
            [
                'name' => 'Employee Onboarding Form',
                'submitters' => [
                    ['name' => 'New Employee', 'uuid' => (string) Str::uuid()],
                ],
            ],
        ];

        foreach ($templates as $templateData) {
            $submitters = $templateData['submitters'];
            $fields = [];
            foreach ($submitters as $submitter) {
                $fields[] = [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Full Name',
                    'type' => 'text',
                    'required' => true,
                    'submitter_uuid' => $submitter['uuid'],
                ];
                $fields[] = [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Signature',
                    'type' => 'signature',
                    'required' => true,
                    'submitter_uuid' => $submitter['uuid'],
                ];
                $fields[] = [
                    'uuid' => (string) Str::uuid(),
                    'name' => 'Date',
                    'type' => 'date',
                    'required' => true,
                    'submitter_uuid' => $submitter['uuid'],
                ];
            }

            Template::firstOrCreate(
                ['name' => $templateData['name'], 'account_id' => $account->id],
                [
                    'slug' => Str::random(14),
                    'author_id' => $user->id,
                    'folder_id' => $folder->id,
                    'source' => 'builder',
                    'fields' => $fields,
                    'schema' => [],
                    'submitters' => $submitters,
                    'preferences' => [],
                    'shared_link' => false,
                ]
            );
        }

        $this->command->info('Seeded successfully!');
        $this->command->info('Login: admin@vouch.test / password');
    }
}
