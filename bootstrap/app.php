<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES') ?: null);

        // Stripe cannot submit a browser CSRF token; the endpoint verifies
        // Stripe's HMAC signature and timestamp instead.
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request): Response {
            // Keep API responses machine-readable and preserve the framework's
            // unmodified response contract for automated tests.
            if ($request->is('api/*') || $request->expectsJson() || app()->environment('testing')) {
                return $response;
            }

            if ($response->getStatusCode() === 419) {
                return back()->with('error', 'Your session expired. Please try again.');
            }

            if (in_array($response->getStatusCode(), [403, 404, 500, 503], true)) {
                return Inertia::render('Error', [
                    'status' => $response->getStatusCode(),
                ])->toResponse($request)->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })->create();
