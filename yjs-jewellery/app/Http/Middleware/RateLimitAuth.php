<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limiting middleware specifically for authentication endpoints
 * Provides protection against brute force attacks
 */
class RateLimitAuth
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next, string $type = 'login'): Response
    {
        $key = $this->resolveRequestSignature($request, $type);
        $limits = $this->getLimits($type);

        if ($this->limiter->tooManyAttempts($key, $limits['max_attempts'])) {
            $seconds = $this->limiter->availableIn($key);

            return response()->json([
                'success' => false,
                'message' => "Too many {$type} attempts. Please try again in {$seconds} seconds.",
                'retry_after' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $this->limiter->hit($key, $limits['decay_seconds']);

        $response = $next($request);

        // Clear rate limit on successful authentication
        if ($response->getStatusCode() === 200 && in_array($type, ['login', 'otp'])) {
            $this->limiter->clear($key);
        }

        return $response;
    }

    protected function resolveRequestSignature(Request $request, string $type): string
    {
        $identifier = $request->input('email')
            ?? $request->input('phone')
            ?? $request->ip();

        return "auth:{$type}:" . sha1($identifier . '|' . $request->ip());
    }

    protected function getLimits(string $type): array
    {
        return match ($type) {
            'login' => [
                'max_attempts' => 5,
                'decay_seconds' => 300, // 5 minutes
            ],
            'register' => [
                'max_attempts' => 3,
                'decay_seconds' => 3600, // 1 hour
            ],
            'otp' => [
                'max_attempts' => 5,
                'decay_seconds' => 300, // 5 minutes
            ],
            'password_reset' => [
                'max_attempts' => 3,
                'decay_seconds' => 3600, // 1 hour
            ],
            'otp_resend' => [
                'max_attempts' => 3,
                'decay_seconds' => 60, // 1 minute
            ],
            default => [
                'max_attempts' => 10,
                'decay_seconds' => 60,
            ],
        };
    }
}
