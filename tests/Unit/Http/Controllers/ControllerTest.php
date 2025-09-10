<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Test implementation of abstract Controller for testing
 */
class TestController extends Controller
{
    public function testAuthorize(string $permission): void
    {
        $this->authorize($permission);
    }
}

class ControllerTest extends TestCase
{
    use RefreshDatabase;

    private TestController $controller;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new TestController();
        // Ensure user ID is not 1 (system admin bypasses all permissions)
        $this->user = User::factory()->create([
            'id' => 2,
        ]);
    }

    /**
     * Test authorize method allows access when Gate returns true
     */
    public function testAuthorizeAllowsAccessWhenGateReturnsTrue(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'test.permission',
        ]);
        $role = Role::create([
            'name' => 'test-role',
        ]);
        $role->permissions()->attach($permission);
        $this->user->roles()->attach($role);

        PermissionService::defineGates();
        $this->actingAs($this->user);

        // Act & Assert - Should not throw exception
        $this->controller->testAuthorize('test.permission');
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /**
     * Test authorize method throws AuthorizationException when Gate returns false
     */
    public function testAuthorizeThrowsExceptionWhenGateReturnsFalse(): void
    {
        // Arrange
        Permission::create([
            'name' => 'test.permission',
        ]);
        PermissionService::defineGates();
        $this->actingAs($this->user);

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('This action is unauthorized.');

        $this->controller->testAuthorize('test.permission');
    }

    /**
     * Test authorize method with non-existent permission
     */
    public function testAuthorizeThrowsExceptionForNonExistentPermission(): void
    {
        // Arrange
        $this->actingAs($this->user);

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('This action is unauthorized.');

        $this->controller->testAuthorize('non.existent.permission');
    }

    /**
     * Test authorize method without authenticated user
     */
    public function testAuthorizeThrowsExceptionWithoutAuthenticatedUser(): void
    {
        // Arrange
        Permission::create([
            'name' => 'test.permission',
        ]);
        PermissionService::defineGates();

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('This action is unauthorized.');

        $this->controller->testAuthorize('test.permission');
    }

    /**
     * Test authorize method with multiple sequential checks
     */
    public function testAuthorizeWorksCorrectlyWithMultipleSequentialChecks(): void
    {
        // Arrange
        $permission1 = Permission::create([
            'name' => 'permission.one',
        ]);
        $permission2 = Permission::create([
            'name' => 'permission.two',
        ]);
        $role = Role::create([
            'name' => 'multi-permission-role',
        ]);
        $role->permissions()->attach([$permission1->id, $permission2->id]);
        $this->user->roles()->attach($role);

        PermissionService::defineGates();
        $this->actingAs($this->user);

        // Act & Assert - Both should pass
        $this->controller->testAuthorize('permission.one');
        $this->controller->testAuthorize('permission.two');
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /**
     * Test authorize method uses Gate facade correctly
     */
    public function testAuthorizeUsesGateFacadeCorrectly(): void
    {
        // Arrange
        $this->actingAs($this->user);

        // Mock Gate facade
        Gate::shouldReceive('allows')
            ->once()
            ->with('mocked.permission')
            ->andReturn(true);

        // Act
        $this->controller->testAuthorize('mocked.permission');

        // Assert - If no exception, test passes
        $this->assertTrue(true);
    }

    /**
     * Test authorize method with empty permission string throws exception
     */
    public function testAuthorizeWithEmptyPermissionThrowsException(): void
    {
        // Arrange
        $this->actingAs($this->user);

        // Act & Assert
        $this->expectException(AuthorizationException::class);

        $this->controller->testAuthorize('');
    }

    /**
     * Test authorize method preserves exception message
     */
    public function testAuthorizePreservesExceptionMessage(): void
    {
        // Arrange
        $this->actingAs($this->user);

        // Act
        try {
            $this->controller->testAuthorize('denied.permission');
            $this->fail('Expected AuthorizationException was not thrown');
        } catch (AuthorizationException $e) {
            // Assert
            $this->assertEquals('This action is unauthorized.', $e->getMessage());
        }
    }

    /**
     * Test authorize method with user having permission through multiple roles
     */
    public function testAuthorizeAllowsAccessWithPermissionFromAnyRole(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'shared.permission',
        ]);

        $role1 = Role::create([
            'name' => 'role-one',
        ]);
        $role2 = Role::create([
            'name' => 'role-two',
        ]);

        // Only role2 has the permission
        $role2->permissions()->attach($permission);

        // User has both roles
        $this->user->roles()->attach([$role1->id, $role2->id]);

        PermissionService::defineGates();
        $this->actingAs($this->user);

        // Act & Assert - Should not throw exception
        $this->controller->testAuthorize('shared.permission');
        $this->assertTrue(true);
    }

    /**
     * Test authorize method is case-sensitive for permissions
     */
    public function testAuthorizeIsCaseSensitiveForPermissions(): void
    {
        // Arrange
        $permission = Permission::create([
            'name' => 'case.sensitive',
        ]);
        $role = Role::create([
            'name' => 'test-role',
        ]);
        $role->permissions()->attach($permission);
        $this->user->roles()->attach($role);

        PermissionService::defineGates();
        $this->actingAs($this->user);

        // Act & Assert - Correct case should work
        $this->controller->testAuthorize('case.sensitive');

        // Wrong case should throw exception
        $this->expectException(AuthorizationException::class);
        $this->controller->testAuthorize('CASE.SENSITIVE');
    }
}
