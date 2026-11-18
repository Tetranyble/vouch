<?php

return [
    'multi_tenant' => true,
    'storage_driver' => env('VOUCH_STORAGE', 'local'),
    'signing' => [
        'require_email_2fa' => false,
    ],
];
