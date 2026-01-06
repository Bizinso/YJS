<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RateLimitAuth;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class RateLimitAuthTest extends TestCase
{
    protected RateLimitAuth $middleware;
    protected RateLimiter $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limiter = app(RateLimiter::class);
        $this->middleware = new RateLimitAuth($this->limiter);
    }

    public function test_allows_requests_under_limit(): void
    {
        $email = 'test_' . uniqid() . '@example.com';
        $request = Request::create('/api/login', 'POST', ['email' => $email]);

        $response = $this->middleware->handle($request, function () {
            return new Response('OK', 200);
        }, 'login');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_blocks_requests_over_limit(): void
    {
        $email = 'blocked_' . uniqid() . '@example.com';
        $request = Request::create('/api/login', 'POST', ['email' => $email]);

        // Make 5 failed attempts (limit is 5)
        for ($i = 0; $i < 5; $i++) {
            $this->middleware->handle($request, function () {
                return new Response('Unauthorized', 401);
            }, 'login');
        }

        // 6th request should be blocked
        $response = $this->middleware->handle($request, function () {
            return new Response('OK', 200);
        }, 'login');

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertStringContainsString('Too many login attempts', $response->getContent());
    }

    public function test_otp_resend_has_stricter_limit(): void
    {
        $phone = '98765' . rand(10000, 99999);
        $request = Request::create('/api/otp/resend', 'POST', ['phone' => $phone]);

        // Make 3 requests (limit is 3 for otp_resend)
        for ($i = 0; $i < 3; $i++) {
            $this->middleware->handle($request, function () {
                return new Response('OK', 200);
            }, 'otp_resend');
        }

        // 4th request should be blocked
        $response = $this->middleware->handle($request, function () {
            return new Response('OK', 200);
        }, 'otp_resend');

        $this->assertEquals(429, $response->getStatusCode());
    }

    public function test_clears_limit_on_successful_login(): void
    {
        $email = 'success_' . uniqid() . '@example.com';
        $request = Request::create('/api/login', 'POST', ['email' => $email]);

        // Make some failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->middleware->handle($request, function () {
                return new Response('Unauthorized', 401);
            }, 'login');
        }

        // Successful login should clear the limit
        $response = $this->middleware->handle($request, function () {
            return new Response('OK', 200);
        }, 'login');

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        // Clear rate limiter cache - flush all to ensure clean state
        app('cache')->flush();
        parent::tearDown();
    }
}
