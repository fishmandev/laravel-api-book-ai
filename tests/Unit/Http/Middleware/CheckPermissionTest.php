<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\CheckPermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CheckPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected CheckPermission $middleware;

    protected Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckPermission();
        $this->request = Request::create('/test', 'GET');
    }

    /**
     * Test middleware allows request when Gate::allows returns true
     */
    public function testAllowsRequestWhenGateReturnsTrue(): void
    {
        // Arrange
        Gate::shouldReceive('allows')
            ->once()
            ->with('books.view')
            ->andReturn(true);

        $expectedResponse = new Response('success');
        $next = fn ($request) => $expectedResponse;

        // Act
        $response = $this->middleware->handle($this->request, $next, 'books.view');

        // Assert
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Test middleware throws AuthorizationException when Gate::allows returns false
     */
    public function testThrowsAuthorizationExceptionWhenGateReturnsFalse(): void
    {
        // Arrange
        Gate::shouldReceive('allows')
            ->once()
            ->with('books.delete')
            ->andReturn(false);

        $next = fn ($request) => new Response('should not reach here');

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->middleware->handle($this->request, $next, 'books.delete');
    }

    /**
     * Test middleware handles unauthenticated users (Gate returns false)
     */
    public function testHandlesUnauthenticatedUsers(): void
    {
        // Arrange - simulate unauthenticated scenario
        Gate::shouldReceive('allows')
            ->once()
            ->with('protected.resource')
            ->andReturn(false);

        $next = fn ($request) => new Response('should not reach here');

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->middleware->handle($this->request, $next, 'protected.resource');
    }

    /**
     * Test that exceptions from next middleware are not caught
     *
     * @codeCoverageIgnore This test verifies absence of try-catch, not executable code
     */
    public function testDoesNotCatchExceptionsFromNextMiddleware(): void
    {
        // Arrange
        Gate::shouldReceive('allows')
            ->once()
            ->with('books.view')
            ->andReturn(true);

        $next = function ($request) {
            throw new \RuntimeException('Downstream error');
        };

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Downstream error');
        $this->middleware->handle($this->request, $next, 'books.view');
    }
}
