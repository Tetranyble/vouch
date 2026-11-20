<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $urls = [
            ['loc' => $baseUrl.'/', 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => $baseUrl.'/security', 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => $baseUrl.'/privacy', 'changefreq' => 'yearly', 'priority' => '0.5'],
            ['loc' => $baseUrl.'/terms', 'changefreq' => 'yearly', 'priority' => '0.5'],
        ];

        return response()
            ->view('sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
