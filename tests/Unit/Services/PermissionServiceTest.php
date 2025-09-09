<?php

namespace Tests\Unit\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test defineGates creates gates for all permissions in database
     */
    public function test_define_gates_creates_gates_for_all_permissions(): void
    {
        // Arrange
        Permission::create(['name' => 'books.list']);
        Permission::create(['name' => 'books.create']);
        Permission::create(['name' => 'books.view']);
        
        // Act
        PermissionService::defineGates();

        // Assert
        $this->assertTrue(Gate::has('books.list'));
        $this->assertTrue(Gate::has('books.create'));
        $this->assertTrue(Gate::has('books.view'));
    }

    /**
     * Test defineGates skips when permissions table doesn't exist
     */
    public function test_define_gates_skips_when_permissions_table_does_not_exist(): void
    {
        // Arrange
        Schema::dropIfExists('permissions');
        
        // Act
        PermissionService::defineGates();

        // Assert - Should not throw exception
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /**
     * Test gate allows access when user has permission
     */
    public function test_gate_allows_access_when_user_has_permission(): void
    {
        // Arrange
        $user = User::factory()->create(['id' => 2]);
        $permission = Permission::create(['name' => 'books.edit']);
        $role = Role::create(['name' => 'editor']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);
        
        PermissionService::defineGates();

        // Act
        $this->actingAs($user);
        $allowed = Gate::allows('books.edit');

        // Assert
        $this->assertTrue($allowed);
    }

    /**
     * Test gate denies access when user doesn't have permission
     */
    public function test_gate_denies_access_when_user_does_not_have_permission(): void
    {
        // Arrange
        $user = User::factory()->create(['id' => 2]);
        Permission::create(['name' => 'books.delete']);
        
        PermissionService::defineGates();

        // Act
        $this->actingAs($user);
        $allowed = Gate::allows('books.delete');

        // Assert
        $this->assertFalse($allowed);
    }

    /**
     * Test gate works with multiple permissions
     */
    public function test_gate_works_correctly_with_multiple_permissions(): void
    {
        // Arrange
        $user = User::factory()->create(['id' => 2]);
        $permission1 = Permission::create(['name' => 'books.list']);
        $permission2 = Permission::create(['name' => 'books.create']);
        $permission3 = Permission::create(['name' => 'books.delete']);
        
        $role = Role::create(['name' => 'manager']);
        $role->permissions()->attach([$permission1->id, $permission2->id]);
        $user->roles()->attach($role);
        
        PermissionService::defineGates();

        // Act & Assert
        $this->actingAs($user);
        $this->assertTrue(Gate::allows('books.list'));
        $this->assertTrue(Gate::allows('books.create'));
        $this->assertFalse(Gate::allows('books.delete'));
    }

    /**
     * Test gates handle non-existent permission gracefully
     */
    public function test_gate_denies_non_existent_permission(): void
    {
        // Arrange
        $user = User::factory()->create(['id' => 2]);
        Permission::create(['name' => 'books.list']);
        
        PermissionService::defineGates();

        // Act
        $this->actingAs($user);
        $allowed = Gate::allows('non.existent.permission');

        // Assert
        $this->assertFalse($allowed);
    }

    /**
     * Test gates work with user having multiple roles
     */
    public function test_gates_work_with_user_having_multiple_roles(): void
    {
        // Arrange
        $user = User::factory()->create(['id' => 2]);
        
        $permission1 = Permission::create(['name' => 'books.list']);
        $permission2 = Permission::create(['name' => 'books.create']);
        $permission3 = Permission::create(['name' => 'books.delete']);
        
        $role1 = Role::create(['name' => 'viewer']);
        $role1->permissions()->attach($permission1);
        
        $role2 = Role::create(['name' => 'creator']);
        $role2->permissions()->attach($permission2);
        
        $user->roles()->attach([$role1->id, $role2->id]);
        
        PermissionService::defineGates();

        // Act & Assert
        $this->actingAs($user);
        $this->assertTrue(Gate::allows('books.list'));
        $this->assertTrue(Gate::allows('books.create'));
        $this->assertFalse(Gate::allows('books.delete'));
    }

    /**
     * Test defineGates handles empty permissions table
     */
    public function test_define_gates_handles_empty_permissions_table(): void
    {
        // Arrange - Ensure permissions table is empty
        Permission::query()->delete();
        
        // Act
        PermissionService::defineGates();

        // Assert - Should not throw exception
        $this->assertTrue(true);
    }

    /**
     * Test gate closure receives correct user parameter
     */
    public function test_gate_closure_receives_correct_user_parameter(): void
    {
        // Arrange
        $user1 = User::factory()->create(['id' => 2]);
        $user2 = User::factory()->create(['id' => 3]);
        
        $permission = Permission::create(['name' => 'test.permission']);
        $role = Role::create(['name' => 'test-role']);
        $role->permissions()->attach($permission);
        $user1->roles()->attach($role);
        
        PermissionService::defineGates();

        // Act & Assert
        $this->actingAs($user1);
        $this->assertTrue(Gate::allows('test.permission'));
        
        $this->actingAs($user2);
        $this->assertFalse(Gate::allows('test.permission'));
    }

    /**
     * Test defineGates is idempotent (can be called multiple times)
     */
    public function test_define_gates_is_idempotent(): void
    {
        // Arrange
        Permission::create(['name' => 'test.permission']);
        
        // Act
        PermissionService::defineGates();
        PermissionService::defineGates(); // Call twice
        
        // Assert
        $this->assertTrue(Gate::has('test.permission'));
    }
}