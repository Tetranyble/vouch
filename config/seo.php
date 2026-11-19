<?php

return [
    'title' => env('SEO_TITLE', 'Vouch — Secure Document Signing'),

    'description' => env(
        'SEO_DESCRIPTION',
        'Send, sign, track, and protect important agreements with Vouch, a secure document-signing workspace for modern teams.'
    ),

    // This must be an absolute, publicly accessible URL in production.
    'image' => env('SEO_IMAGE_URL', rtrim((string) env('APP_URL', ''), '/').'/social-share.png'),

    'twitter_site' => env('SEO_TWITTER_SITE'),
];
