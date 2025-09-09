<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\AuthController;
use App\Http\Requests\LoginRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected AuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AuthController();
    }

    /**
     * Test successful login returns correct response structure
     */
    public function test_successful_login_returns_correct_response_structure(): void
    {
        // Mock the LoginRequest
        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn([
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

        // Mock successful authentication
        $fakeToken = 'fake-jwt-token-string';
        Auth::shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'test@example.com',
                'password' => 'password123',
            ])
            ->andReturn($fakeToken);

        $response = $this->controller->login($request);
        $responseData = $response->getData(true);

        // Assert response type
        $this->assertInstanceOf(JsonResponse::class, $response);
        
        // Assert status code
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        // Assert response structure
        $this->assertArrayHasKey('access_token', $responseData);
        $this->assertArrayHasKey('token_type', $responseData);
        $this->assertArrayHasKey('expires_in', $responseData);
        
        // Assert response values
        $this->assertEquals($fakeToken, $responseData['access_token']);
        $this->assertEquals('bearer', $responseData['token_type']);
        $this->assertEquals(config('jwt.ttl') * 60, $responseData['expires_in']);
    }

    /**
     * Test failed login returns unauthorized response
     */
    #[DataProvider('failedAuthenticationProvider')]
    public function test_failed_login_returns_unauthorized_response($authResult): void
    {
        // Mock the LoginRequest
        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn([
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

        // Mock failed authentication with different return values
        Auth::shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ])
            ->andReturn($authResult);

        $response = $this->controller->login($request);
        $responseData = $response->getData(true);

        // Assert response type
        $this->assertInstanceOf(JsonResponse::class, $response);
        
        // Assert status code
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        
        // Assert error message
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Invalid credentials', $responseData['message']);
    }

    /**
     * Provide different failed authentication scenarios
     */
    public static function failedAuthenticationProvider(): array
    {
        return [
            'returns false' => [false],
            'returns null' => [null],
            'returns empty string' => [''],
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}