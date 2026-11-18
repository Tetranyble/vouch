<?php

return [
    // Reuse existing Vouch account/user models as storage workspace and actor.
    // The package owns media, folder, quota, audit and lifecycle tables.
    'models' => [
        'workspace' => \App\Models\Account::class,
        'user' => \App\Models\User::class,
    ],
    'workspace' => [
        'workspace_relation' => 'account',
        'workspace_foreign_key' => 'account_id',
        'resource_foreign_key' => 'workspace_id',
    ],
    // Vouch controls downloads itself. Never publish generic storage endpoints by default.
    'routes' => ['enabled' => false],
    'default_disk' => env('STORAGE_DISK', 'local'),
];
