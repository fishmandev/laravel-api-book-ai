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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test successful login returns correct response structure
     */
    public function testSuccessfulLoginReturnsCorrectResponseStructure(): void
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
     * Test failed login throws authentication exception
     */
    #[DataProvider('failedAuthenticationProvider')]
    public function testFailedLoginThrowsAuthenticationException($authResult): void
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

        // Expect AuthenticationException to be thrown
        $this->expectException(\Illuminate\Auth\AuthenticationException::class);
        $this->expectExceptionMessage('Invalid credentials');

        // This should throw the exception
        $this->controller->login($request);
    }

    /**
     * Test that exception message matches expected value
     */
    public function testExceptionMessageIsCorrect(): void
    {
        // Mock the LoginRequest
        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn([
                'email' => 'invalid@test.com',
                'password' => 'invalid',
            ]);

        // Mock failed authentication
        Auth::shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'invalid@test.com',
                'password' => 'invalid',
            ])
            ->andReturn(false);

        try {
            $this->controller->login($request);
            $this->fail('Expected AuthenticationException was not thrown');
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            // Assert the exact exception message
            $this->assertEquals('Invalid credentials', $e->getMessage());
        }
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
}
