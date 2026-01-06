<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    protected AuditLog $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuditLog();
    }

    public function test_logs_delete_requests(): void
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Log', \Mockery::type('array'));

        $request = Request::create('/api/admin/products/1', 'DELETE');

        $this->middleware->handle($request, function () {
            return new Response('', 200);
        });
    }

    public function test_logs_admin_post_requests(): void
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Log', \Mockery::type('array'));

        // Path must match 'admin/*' pattern (without leading 'api/')
        $request = Request::create('/admin/products', 'POST', ['name' => 'Test Product']);

        $this->middleware->handle($request, function () {
            return new Response('', 201);
        });
    }

    public function test_does_not_log_get_requests(): void
    {
        Log::shouldReceive('channel')->never();

        $request = Request::create('/api/admin/products', 'GET');

        $this->middleware->handle($request, function () {
            return new Response('', 200);
        });
    }

    public function test_sanitizes_sensitive_data(): void
    {
        $loggedData = null;

        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Log', \Mockery::on(function ($data) use (&$loggedData) {
                $loggedData = $data;
                return true;
            }));

        // Path must match 'admin/*' pattern (without leading 'api/')
        $request = Request::create('/admin/users', 'POST', [
            'email' => 'test@example.com',
            'password' => 'secret123',
            'api_key' => 'key123',
        ]);

        $this->middleware->handle($request, function () {
            return new Response('', 201);
        });

        $this->assertEquals('[REDACTED]', $loggedData['request_body']['password']);
        $this->assertEquals('[REDACTED]', $loggedData['request_body']['api_key']);
        $this->assertEquals('test@example.com', $loggedData['request_body']['email']);
    }

    public function test_logs_order_status_changes(): void
    {
        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('Audit Log', \Mockery::type('array'));

        $request = Request::create('/api/admin/orders/123/status', 'PATCH', ['status' => 'shipped']);

        $this->middleware->handle($request, function () {
            return new Response('', 200);
        });
    }
}
