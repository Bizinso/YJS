<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content Security Policy middleware
 * Adds security headers to protect against XSS, clickjacking, and other attacks
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only add headers to HTML responses
        if ($this->shouldAddHeaders($response)) {
            $this->addSecurityHeaders($response);
        }

        return $response;
    }

    protected function shouldAddHeaders(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html') || empty($contentType);
    }

    protected function addSecurityHeaders(Response $response): void
    {
        // Content Security Policy
        $csp = $this->buildContentSecurityPolicy();
        $response->headers->set('Content-Security-Policy', $csp);

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Enable XSS filter
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature-Policy)
        $response->headers->set('Permissions-Policy', $this->buildPermissionsPolicy());

        // Strict Transport Security (HSTS)
        if (config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }
    }

    protected function buildContentSecurityPolicy(): string
    {
        $appUrl = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        $policies = [
            // Default fallback
            "default-src 'self'",

            // Scripts - allow self, inline (for Vue), and trusted CDNs
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://checkout.razorpay.com",

            // Styles - allow self and inline (for Tailwind)
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",

            // Images - allow self, data URIs, and blob
            "img-src 'self' data: blob: https: http:",

            // Fonts
            "font-src 'self' https://fonts.gstatic.com data:",

            // Connect - API calls
            "connect-src 'self' https://api.razorpay.com https://lumberjack.razorpay.com wss:",

            // Frames - for payment gateway
            "frame-src 'self' https://api.razorpay.com https://checkout.razorpay.com",

            // Object sources
            "object-src 'none'",

            // Base URI
            "base-uri 'self'",

            // Form actions
            "form-action 'self'",

            // Frame ancestors - prevent embedding
            "frame-ancestors 'self'",

            // Block mixed content
            "block-all-mixed-content",

            // Upgrade insecure requests in production
            config('app.env') === 'production' ? "upgrade-insecure-requests" : "",
        ];

        return implode('; ', array_filter($policies));
    }

    protected function buildPermissionsPolicy(): string
    {
        $permissions = [
            'accelerometer=()',
            'camera=()',
            'geolocation=(self)',
            'gyroscope=()',
            'magnetometer=()',
            'microphone=()',
            'payment=(self)',
            'usb=()',
        ];

        return implode(', ', $permissions);
    }
}
