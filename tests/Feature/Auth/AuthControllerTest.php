<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ?User $testUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user for authentication tests (will be used by most tests)
        $this->testUser = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    /**
     * Test successful login with valid credentials
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ])
            ->assertJson([
                'token_type' => 'bearer',
            ]);

        // Verify the token is valid
        $token = $response->json('access_token');
        $this->assertNotEmpty($token);
        
        // Verify token can be used to authenticate
        $payload = JWTAuth::setToken($token)->getPayload();
        $this->assertEquals($this->testUser->id, $payload['sub']);
    }

    /**
     * Test login fails with invalid email
     */
    public function test_login_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test login fails with invalid password
     */
    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test login fails when email is missing
     */
    public function test_login_fails_when_email_is_missing(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['The email field is required.'],
            ]);
    }

    /**
     * Test login fails when password is missing
     */
    public function test_login_fails_when_password_is_missing(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['The password field is required.'],
            ]);
    }

    /**
     * Test login fails when both email and password are missing
     */
    public function test_login_fails_when_credentials_are_missing(): void
    {
        $response = $this->postJson('/api/v1/login', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test login fails with invalid email format
     */
    public function test_login_fails_with_invalid_email_format(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['The email field must be a valid email address.'],
            ]);
    }

    /**
     * Test system user can login successfully
     */
    public function test_system_user_can_login(): void
    {
        // Truncate users table and create system user with ID=1
        \DB::table('users')->truncate();
        
        $systemUser = User::create([
            'id' => 1,
            'name' => 'System Administrator',
            'email' => 'system@example.com',
            'password' => Hash::make('system123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'system@example.com',
            'password' => 'system123',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);

        // Verify it's the system user (ID = 1)
        $token = $response->json('access_token');
        $payload = JWTAuth::setToken($token)->getPayload();
        $this->assertEquals(1, $payload['sub']);
        
        // Verify system user has special permissions
        $this->assertTrue($systemUser->hasPermission('any.permission'));
    }

    /**
     * Test token expires_in value matches JWT TTL configuration
     */
    public function test_token_expiry_matches_jwt_ttl_configuration(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        
        $expiresIn = $response->json('expires_in');
        $expectedTtl = config('jwt.ttl') * 60; // Convert minutes to seconds
        
        $this->assertEquals($expectedTtl, $expiresIn);
    }

    /**
     * Test login endpoint accepts only POST method
     */
    public function test_login_endpoint_accepts_only_post_method(): void
    {
        // Test GET method
        $response = $this->getJson('/api/v1/login');
        $response->assertStatus(Response::HTTP_METHOD_NOT_ALLOWED);

        // Test PUT method
        $response = $this->putJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        $response->assertStatus(Response::HTTP_METHOD_NOT_ALLOWED);

        // Test DELETE method
        $response = $this->deleteJson('/api/v1/login');
        $response->assertStatus(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test multiple failed login attempts (basic rate limiting awareness)
     */
    public function test_multiple_failed_login_attempts(): void
    {
        // Attempt multiple failed logins
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

            $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        }

        // Verify user can still login with correct credentials
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    /**
     * Test JWT token can be used to access protected routes
     */
    public function test_jwt_token_can_be_used_for_authentication(): void
    {
        // Login to get token
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $token = $response->json('access_token');

        // Use token to authenticate (this would be used with protected routes)
        $authenticatedUser = JWTAuth::setToken($token)->authenticate();
        
        $this->assertInstanceOf(User::class, $authenticatedUser);
        $this->assertEquals($this->testUser->id, $authenticatedUser->id);
        $this->assertEquals($this->testUser->email, $authenticatedUser->email);
    }

    /**
     * Test login response contains valid JWT structure
     */
    public function test_login_response_contains_valid_jwt_structure(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $token = $response->json('access_token');
        
        // JWT should have three parts separated by dots
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
        
        // Verify each part is base64 encoded
        foreach ($parts as $part) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $part);
        }
    }

    /**
     * Test case sensitivity of email in login
     */
    public function test_email_is_case_sensitive_for_login(): void
    {
        // Try with uppercase email (should fail with default Laravel behavior)
        $response = $this->postJson('/api/v1/login', [
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'password123',
        ]);

        // By default, Laravel's authentication is case-sensitive for emails
        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);

        // Try with correct case (should succeed)
        $response = $this->postJson('/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);
    }
}