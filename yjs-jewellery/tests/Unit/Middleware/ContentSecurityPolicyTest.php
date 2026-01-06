<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ContentSecurityPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class ContentSecurityPolicyTest extends TestCase
{
    protected ContentSecurityPolicy $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ContentSecurityPolicy();
    }

    public function test_adds_csp_header_to_html_response(): void
    {
        $request = Request::create('/');
        $response = new Response('<html></html>', 200, ['Content-Type' => 'text/html']);

        $result = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertTrue($result->headers->has('Content-Security-Policy'));
    }

    public function test_adds_x_frame_options_header(): void
    {
        $request = Request::create('/');
        $response = new Response('<html></html>', 200, ['Content-Type' => 'text/html']);

        $result = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertEquals('SAMEORIGIN', $result->headers->get('X-Frame-Options'));
    }

    public function test_adds_x_content_type_options_header(): void
    {
        $request = Request::create('/');
        $response = new Response('<html></html>', 200, ['Content-Type' => 'text/html']);

        $result = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertEquals('nosniff', $result->headers->get('X-Content-Type-Options'));
    }

    public function test_adds_referrer_policy_header(): void
    {
        $request = Request::create('/');
        $response = new Response('<html></html>', 200, ['Content-Type' => 'text/html']);

        $result = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertEquals('strict-origin-when-cross-origin', $result->headers->get('Referrer-Policy'));
    }

    public function test_adds_permissions_policy_header(): void
    {
        $request = Request::create('/');
        $response = new Response('<html></html>', 200, ['Content-Type' => 'text/html']);

        $result = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertTrue($result->headers->has('Permissions-Policy'));
    }

    public function test_csp_includes_razorpay_domains(): void
    {
        $request = Request::create('/');
        $response = new Response('<html></html>', 200, ['Content-Type' => 'text/html']);

        $result = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $csp = $result->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('checkout.razorpay.com', $csp);
        $this->assertStringContainsString('api.razorpay.com', $csp);
    }

    public function test_does_not_add_hsts_in_non_production(): void
    {
        config(['app.env' => 'local']);

        $request = Request::create('/');
        $response = new Response('<html></html>', 200, ['Content-Type' => 'text/html']);

        $result = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertFalse($result->headers->has('Strict-Transport-Security'));
    }
}
