<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PwaController extends Controller
{
    public function manifest(): JsonResponse
    {
        return response()->json([
            'name'             => config('app.name', 'Vouch'),
            'short_name'       => 'Vouch',
            'description'      => 'Digital document signing',
            'start_url'        => '/',
            'display'          => 'standalone',
            'background_color' => '#ffffff',
            'theme_color'      => '#6366f1',
            'icons'            => [
                [
                    'src'   => '/favicon.svg',
                    'sizes' => 'any',
                    'type'  => 'image/svg+xml',
                ],
            ],
        ]);
    }
}
