<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Request Logging Middleware
 *
 * Logs all API requests for debugging and audit purposes.
 */
class LogApiRequests
{
    /**
     * Sensitive fields to mask in logs
     */
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'otp',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'cvv',
        'card_number',
    ];

    /**
     * Routes to skip logging (high frequency, low value)
     */
    protected array $skipRoutes = [
        'api/health',
        'api/ping',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Skip logging for certain routes
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Process request
        $response = $next($request);

        // Calculate duration
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        // Log request
        $this->logRequest($request, $response, $duration);

        return $response;
    }

    /**
     * Check if logging should be skipped
     */
    protected function shouldSkip(Request $request): bool
    {
        foreach ($this->skipRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log the request and response
     */
    protected function logRequest(Request $request, Response $response, float $duration): void
    {
        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'user_type' => $request->user()?->user_type,
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_agent' => substr($request->userAgent() ?? '', 0, 200),
        ];

        // Add request body for non-GET requests (masked)
        if (!in_array($request->method(), ['GET', 'HEAD'])) {
            $logData['request_body'] = $this->maskSensitiveData($request->all());
        }

        // Log based on response status
        if ($response->getStatusCode() >= 500) {
            Log::channel('api')->error('API Request', $logData);
        } elseif ($response->getStatusCode() >= 400) {
            Log::channel('api')->warning('API Request', $logData);
        } else {
            Log::channel('api')->info('API Request', $logData);
        }
    }

    /**
     * Mask sensitive data in the log
     */
    protected function maskSensitiveData(array $data): array
    {
        $masked = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $this->sensitiveFields)) {
                $masked[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                $masked[$key] = $this->maskSensitiveData($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }
}
