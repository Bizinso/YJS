<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'partner' => \App\Http\Middleware\EnsurePartner::class,
            'customer' => \App\Http\Middleware\EnsureCustomer::class,
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
            'log.api' => \App\Http\Middleware\LogApiRequests::class,
            'rate.auth' => \App\Http\Middleware\RateLimitAuth::class,
            'audit' => \App\Http\Middleware\AuditLog::class,
            'csp' => \App\Http\Middleware\ContentSecurityPolicy::class,
        ]);

        // Apply global middleware
        $middleware->web(append: [
            \App\Http\Middleware\ContentSecurityPolicy::class,
        ]);

        // Apply throttle and security to API routes
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\AuditLog::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Standardized API error responses
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'You must be logged in to access this resource.',
                    'code' => 401,
                ], 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $e->errors(),
                    'code' => 422,
                ], 422);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'Not found',
                    'message' => 'The requested resource was not found.',
                    'code' => 404,
                ], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'Method not allowed',
                    'message' => 'The HTTP method is not supported for this route.',
                    'code' => 405,
                ], 405);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'HTTP error',
                    'message' => $e->getMessage() ?: 'An error occurred.',
                    'code' => $e->getStatusCode(),
                ], $e->getStatusCode());
            }
        });

        // Catch-all for unexpected exceptions in API routes
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $statusCode = 500;
                $message = config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.';

                return response()->json([
                    'error' => 'Server error',
                    'message' => $message,
                    'code' => $statusCode,
                ], $statusCode);
            }
        });
    })->create();
