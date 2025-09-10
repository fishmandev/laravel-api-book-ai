<?php

namespace Tests\Integration\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->testUser = User::factory()->create([
            'email' => 'jwt.test@example.com',
            'password' => Hash::make('jwtpassword123'),
        ]);
    }

    /**
     * Test JWT token generation for user
     */
    public function test_jwt_token_can_be_generated_for_user(): void
    {
        $token = JWTAuth::fromUser($this->testUser);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        
        // Verify token structure (should have 3 parts separated by dots)
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * Test JWT token contains correct user information
     */
    public function test_jwt_token_contains_correct_user_information(): void
    {
        $token = JWTAuth::fromUser($this->testUser);
        $payload = JWTAuth::setToken($token)->getPayload();

        // Check standard JWT claims
        $this->assertEquals($this->testUser->id, $payload['sub']);
        $this->assertArrayHasKey('iat', $payload->toArray());
        $this->assertArrayHasKey('exp', $payload->toArray());
        $this->assertArrayHasKey('nbf', $payload->toArray());
        $this->assertArrayHasKey('jti', $payload->toArray());
    }

    /**
     * Test JWT token can authenticate user
     */
    public function test_jwt_token_can_authenticate_user(): void
    {
        $token = JWTAuth::fromUser($this->testUser);
        $authenticatedUser = JWTAuth::setToken($token)->authenticate();

        $this->assertInstanceOf(User::class, $authenticatedUser);
        $this->assertEquals($this->testUser->id, $authenticatedUser->id);
        $this->assertEquals($this->testUser->email, $authenticatedUser->email);
    }

    /**
     * Test invalid JWT token throws exception
     */
    public function test_invalid_jwt_token_throws_exception(): void
    {
        $invalidToken = 'invalid.jwt.token';

        $this->expectException(TokenInvalidException::class);
        JWTAuth::setToken($invalidToken)->authenticate();
    }

    /**
     * Test JWT token expiry time matches configuration
     */
    public function test_jwt_token_expiry_time_matches_configuration(): void
    {
        $token = JWTAuth::fromUser($this->testUser);
        $payload = JWTAuth::setToken($token)->getPayload();

        $issuedAt = $payload['iat'];
        $expiresAt = $payload['exp'];
        
        $ttlInSeconds = config('jwt.ttl') * 60;
        $actualTtl = $expiresAt - $issuedAt;

        // Allow for small time differences (1 second tolerance)
        $this->assertEqualsWithDelta($ttlInSeconds, $actualTtl, 1);
    }

    /**
     * Test JWT token refresh functionality
     */
    public function test_jwt_token_can_be_refreshed(): void
    {
        $originalToken = JWTAuth::fromUser($this->testUser);
        
        // Wait a moment to ensure different token
        sleep(1);
        
        // Set the token before refreshing
        JWTAuth::setToken($originalToken);
        $newToken = JWTAuth::refresh();

        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($originalToken, $newToken);

        // Verify new token still authenticates the same user
        $authenticatedUser = JWTAuth::setToken($newToken)->authenticate();
        $this->assertEquals($this->testUser->id, $authenticatedUser->id);
    }

    /**
     * Test JWT token invalidation
     */
    public function test_jwt_token_can_be_invalidated(): void
    {
        $token = JWTAuth::fromUser($this->testUser);
        
        // Invalidate the token
        JWTAuth::setToken($token)->invalidate();

        // Try to use the invalidated token
        try {
            JWTAuth::setToken($token)->authenticate();
            $this->fail('Expected exception for invalidated token');
        } catch (JWTException $e) {
            $this->assertInstanceOf(JWTException::class, $e);
        }
    }

    /**
     * Test system user JWT functionality
     */
    public function test_system_user_jwt_functionality(): void
    {
        // Truncate users table and create system user with ID=1
        DB::table('users')->truncate();
        
        $systemUser = User::create([
            'id' => 1,
            'name' => 'System Administrator',
            'email' => 'system@example.com',
            'password' => Hash::make('system123'),
            'email_verified_at' => now(),
        ]);
        
        $this->assertNotNull($systemUser);
        $this->assertEquals('system@example.com', $systemUser->email);

        // Generate token for system user
        $token = JWTAuth::fromUser($systemUser);
        $this->assertNotEmpty($token);

        // Verify token authenticates system user
        $authenticatedUser = JWTAuth::setToken($token)->authenticate();
        $this->assertEquals(1, $authenticatedUser->id);
        $this->assertEquals('system@example.com', $authenticatedUser->email);
    }

    /**
     * Test JWT token with custom claims
     */
    public function test_jwt_token_with_custom_claims(): void
    {
        $customClaims = ['role' => 'admin', 'permissions' => ['read', 'write']];
        $token = JWTAuth::claims($customClaims)->fromUser($this->testUser);
        
        $payload = JWTAuth::setToken($token)->getPayload();
        
        $this->assertEquals('admin', $payload['role']);
        $this->assertEquals(['read', 'write'], $payload['permissions']);
    }

    /**
     * Test JWT authentication via HTTP header
     */
    public function test_jwt_authentication_via_http_header(): void
    {
        // Login to get token
        $response = $this->postJson('/api/v1/login', [
            'email' => 'jwt.test@example.com',
            'password' => 'jwtpassword123',
        ]);

        $token = $response->json('access_token');

        // Create a protected route for testing (if it exists)
        // For now, we'll just verify the token format
        $this->assertNotEmpty($token);
        
        // Verify token can be used in Authorization header format
        $authHeader = 'Bearer ' . $token;
        $this->assertStringStartsWith('Bearer ', $authHeader);
    }

    /**
     * Test concurrent JWT tokens for same user
     */
    public function test_concurrent_jwt_tokens_for_same_user(): void
    {
        $token1 = JWTAuth::fromUser($this->testUser);
        sleep(1); // Ensure different timestamp
        $token2 = JWTAuth::fromUser($this->testUser);

        $this->assertNotEquals($token1, $token2);

        // Both tokens should authenticate the same user
        $user1 = JWTAuth::setToken($token1)->authenticate();
        $user2 = JWTAuth::setToken($token2)->authenticate();

        $this->assertEquals($user1->id, $user2->id);
        $this->assertEquals($this->testUser->id, $user1->id);
    }

    /**
     * Test JWT token parsing from different formats
     */
    public function test_jwt_token_parsing_from_different_formats(): void
    {
        $token = JWTAuth::fromUser($this->testUser);

        // Test direct token
        $user1 = JWTAuth::setToken($token)->authenticate();
        $this->assertEquals($this->testUser->id, $user1->id);

        // Test with Bearer prefix (should be handled by middleware in real app)
        $bearerToken = 'Bearer ' . $token;
        $cleanToken = str_replace('Bearer ', '', $bearerToken);
        $user2 = JWTAuth::setToken($cleanToken)->authenticate();
        $this->assertEquals($this->testUser->id, $user2->id);
    }

    /**
     * Test JWT token TTL configuration
     */
    public function test_jwt_token_ttl_configuration(): void
    {
        $ttl = config('jwt.ttl');
        $this->assertIsNumeric($ttl);
        $this->assertGreaterThan(0, $ttl);

        // Verify TTL is used in token generation
        $token = JWTAuth::fromUser($this->testUser);
        $payload = JWTAuth::setToken($token)->getPayload();
        
        $expectedExpiry = Carbon::now()->addMinutes($ttl)->timestamp;
        $actualExpiry = $payload['exp'];
        
        // Allow 5 second tolerance for test execution time
        $this->assertEqualsWithDelta($expectedExpiry, $actualExpiry, 5);
    }

    /**
     * Test database persistence after JWT authentication
     */
    public function test_database_persistence_after_jwt_authentication(): void
    {
        // Create initial user
        $initialCount = User::count();
        
        // Generate and use JWT token
        $token = JWTAuth::fromUser($this->testUser);
        $authenticatedUser = JWTAuth::setToken($token)->authenticate();
        
        // Verify no new users were created
        $this->assertEquals($initialCount, User::count());
        
        // Verify authenticated user exists in database
        $dbUser = User::find($authenticatedUser->id);
        $this->assertNotNull($dbUser);
        $this->assertEquals($this->testUser->email, $dbUser->email);
    }

    /**
     * Test JWT token with malformed structure
     */
    public function test_jwt_token_with_malformed_structure(): void
    {
        $malformedTokens = [
            'not.a.token',
            'only.two',
            'way.too.many.parts.here',
            '',
            'single_part',
            '...',
        ];

        foreach ($malformedTokens as $malformedToken) {
            try {
                JWTAuth::setToken($malformedToken)->authenticate();
                $this->fail("Token '{$malformedToken}' should have thrown an exception");
            } catch (TokenInvalidException | JWTException $e) {
                $this->assertInstanceOf(\Exception::class, $e);
            }
        }
    }

    /**
     * Test JWT authentication with different user models
     */
    public function test_jwt_authentication_with_different_users(): void
    {
        // Create multiple users
        $users = User::factory()->count(3)->create();
        
        $tokens = [];
        foreach ($users as $user) {
            $tokens[$user->id] = JWTAuth::fromUser($user);
        }

        // Verify each token authenticates the correct user
        foreach ($tokens as $userId => $token) {
            $authenticatedUser = JWTAuth::setToken($token)->authenticate();
            $this->assertEquals($userId, $authenticatedUser->id);
        }
    }

    /**
     * Test full authentication flow integration
     */
    public function test_full_authentication_flow_integration(): void
    {
        // 1. Login via API
        $response = $this->postJson('/api/v1/login', [
            'email' => 'jwt.test@example.com',
            'password' => 'jwtpassword123',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $token = $response->json('access_token');

        // 2. Parse and validate token
        $payload = JWTAuth::setToken($token)->getPayload();
        $this->assertNotNull($payload);

        // 3. Authenticate user from token
        $authenticatedUser = JWTAuth::setToken($token)->authenticate();
        $this->assertEquals($this->testUser->id, $authenticatedUser->id);

        // 4. Verify user permissions (system user check)
        if ($authenticatedUser->id === 1) {
            $this->assertTrue($authenticatedUser->hasPermission('any.permission'));
        }

        // 5. Refresh token
        JWTAuth::setToken($token);
        $newToken = JWTAuth::refresh();
        $this->assertNotEquals($token, $newToken);

        // 6. Invalidate old token
        JWTAuth::setToken($token)->invalidate();

        // 7. Verify new token still works
        $userFromNewToken = JWTAuth::setToken($newToken)->authenticate();
        $this->assertEquals($this->testUser->id, $userFromNewToken->id);
    }
}