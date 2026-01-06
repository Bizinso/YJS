<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Audit logging middleware for sensitive operations
 * Logs all admin/partner actions for compliance and security
 */
class AuditLog
{
    /**
     * Sensitive operations that should always be logged
     */
    protected array $sensitivePatterns = [
        'DELETE' => ['*'],
        'POST' => [
            'admin/*',
            'partner/*',
            '*/orders/*/cancel',
            '*/orders/*/refund',
            '*/users/*',
            '*/roles/*',
            '*/permissions/*',
            '*/settings/*',
        ],
        'PUT' => [
            'admin/*',
            'partner/*',
            '*/orders/*',
            '*/users/*',
            '*/roles/*',
        ],
        'PATCH' => [
            'admin/*',
            'partner/*',
            '*/orders/*/status',
            '*/users/*/status',
        ],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log if this is a sensitive operation
        if ($this->shouldLog($request)) {
            $this->logAction($request, $response);
        }

        return $response;
    }

    protected function shouldLog(Request $request): bool
    {
        $method = $request->method();
        $path = $request->path();

        // Always log DELETE requests
        if ($method === 'DELETE') {
            return true;
        }

        // Check if path matches any sensitive patterns
        $patterns = $this->sensitivePatterns[$method] ?? [];

        foreach ($patterns as $pattern) {
            if ($this->pathMatches($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected function pathMatches(string $path, string $pattern): bool
    {
        $pattern = str_replace(['*', '/'], ['.*', '\/'], $pattern);

        return (bool) preg_match("/^{$pattern}$/", $path);
    }

    protected function logAction(Request $request, Response $response): void
    {
        $user = Auth::user();

        $logData = [
            'timestamp' => now()->toIso8601String(),
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : 'guest',
            'user_email' => $user?->email,
            'action' => $request->method(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $request->header('X-Request-ID', uniqid('req_')),
            'status_code' => $response->getStatusCode(),
            'response_time_ms' => defined('LARAVEL_START')
                ? round((microtime(true) - LARAVEL_START) * 1000, 2)
                : null,
        ];

        // Log request body for certain operations (excluding sensitive data)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $logData['request_body'] = $this->sanitizeRequestData($request->all());
        }

        // Log to dedicated audit channel
        Log::channel('audit')->info('Audit Log', $logData);
    }

    protected function sanitizeRequestData(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'card_number',
            'cvv',
            'razorpay_key_secret',
        ];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeRequestData($value);
            }
        }

        return $data;
    }
}
