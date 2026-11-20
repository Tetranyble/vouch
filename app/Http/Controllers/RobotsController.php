<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

final class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemap = rtrim((string) config('app.url'), '/').'/sitemap.xml';

        return response(implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /api/',
            'Disallow: /dashboard',
            'Disallow: /templates',
            'Disallow: /submissions',
            'Disallow: /settings',
            'Disallow: /profile',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password/',
            'Disallow: /verify-email',
            'Disallow: /confirm-password',
            'Disallow: /d/',
            'Disallow: /s/',
            '',
            "Sitemap: {$sitemap}",
            '',
        ], "\n"), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
