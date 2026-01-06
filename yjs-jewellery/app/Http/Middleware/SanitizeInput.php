<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Input Sanitization Middleware
 *
 * Sanitizes user input to prevent XSS and other injection attacks.
 */
class SanitizeInput
{
    /**
     * Fields to skip sanitization (e.g., password, HTML content)
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'description', // May contain HTML
        'content', // May contain HTML
        'body', // May contain HTML
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $sanitized = $this->sanitize($input);
        $request->merge($sanitized);

        return $next($request);
    }

    /**
     * Recursively sanitize input array
     */
    protected function sanitize(array $input, string $prefix = ''): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (in_array($key, $this->except)) {
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value, $fullKey);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a string value
     */
    protected function sanitizeString(string $value): string
    {
        // Trim whitespace
        $value = trim($value);

        // Remove null bytes
        $value = str_replace(chr(0), '', $value);

        // Convert special characters to HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        return $value;
    }
}
