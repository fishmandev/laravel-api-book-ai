<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Role model uses correct table
     */
    public function test_uses_correct_table(): void
    {
        $role = new Role();
        
        $this->assertEquals('roles', $role->getTable());
    }

    /**
     * Test that Role has correct fillable attributes
     */
    public function test_has_correct_fillable_attributes(): void
    {
        $role = new Role();
        
        $this->assertEquals(['name'], $role->getFillable());
    }

    /**
     * Test that Role can be created with factory
     */
    public function test_can_be_created_with_factory(): void
    {
        $role = Role::factory()->create();
        
        $this->assertInstanceOf(Role::class, $role);
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => $role->name,
        ]);
    }

    /**
     * Test that Role can be created with mass assignment
     */
    public function test_can_be_created_with_mass_assignment(): void
    {
        $roleData = [
            'name' => 'test_role',
        ];
        
        $role = Role::create($roleData);
        
        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('test_role', $role->name);
        $this->assertDatabaseHas('roles', $roleData);
    }

    /**
     * Test that Role name can be updated
     */
    public function test_name_can_be_updated(): void
    {
        $role = Role::factory()->create(['name' => 'original_role']);
        
        $role->update(['name' => 'updated_role']);
        
        $this->assertEquals('updated_role', $role->fresh()->name);
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'updated_role',
        ]);
    }

    /**
     * Test that Role has users relationship
     */
    public function test_has_users_relationship(): void
    {
        $role = Role::factory()->create();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $role->users()
        );
    }

    /**
     * Test that Role can be attached to users
     */
    public function test_can_be_attached_to_users(): void
    {
        $role = Role::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $role->users()->attach([$user1->id, $user2->id]);
        
        $this->assertCount(2, $role->users);
        $this->assertTrue($role->users->contains($user1));
        $this->assertTrue($role->users->contains($user2));
        
        // Test the pivot table
        $this->assertDatabaseHas('user_role', [
            'role_id' => $role->id,
            'user_id' => $user1->id,
        ]);
        $this->assertDatabaseHas('user_role', [
            'role_id' => $role->id,
            'user_id' => $user2->id,
        ]);
    }

    /**
     * Test that Role can be detached from users
     */
    public function test_can_be_detached_from_users(): void
    {
        $role = Role::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $role->users()->attach([$user1->id, $user2->id]);
        
        // Detach one user
        $role->users()->detach($user1->id);
        
        $this->assertCount(1, $role->fresh()->users);
        $this->assertFalse($role->fresh()->users->contains($user1));
        $this->assertTrue($role->fresh()->users->contains($user2));
        
        // Verify pivot table
        $this->assertDatabaseMissing('user_role', [
            'role_id' => $role->id,
            'user_id' => $user1->id,
        ]);
        $this->assertDatabaseHas('user_role', [
            'role_id' => $role->id,
            'user_id' => $user2->id,
        ]);
    }

    /**
     * Test that Role can sync users
     */
    public function test_can_sync_users(): void
    {
        $role = Role::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        // Initial attachment
        $role->users()->attach([$user1->id, $user2->id]);
        
        // Sync with different users
        $role->users()->sync([$user2->id, $user3->id]);
        
        $this->assertCount(2, $role->fresh()->users);
        $this->assertFalse($role->fresh()->users->contains($user1));
        $this->assertTrue($role->fresh()->users->contains($user2));
        $this->assertTrue($role->fresh()->users->contains($user3));
    }

    /**
     * Test that Role users relationship uses correct pivot table
     */
    public function test_users_relationship_uses_correct_pivot_table(): void
    {
        $role = Role::factory()->create();
        
        $pivotTable = $role->users()->getTable();
        
        $this->assertEquals('user_role', $pivotTable);
    }

    /**
     * Test that Role has permissions relationship
     */
    public function test_has_permissions_relationship(): void
    {
        $role = Role::factory()->create();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $role->permissions()
        );
    }

    /**
     * Test that Role can be attached to permissions
     */
    public function test_can_be_attached_to_permissions(): void
    {
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();
        
        $role->permissions()->attach([$permission1->id, $permission2->id]);
        
        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->permissions->contains($permission1));
        $this->assertTrue($role->permissions->contains($permission2));
        
        // Test the pivot table
        $this->assertDatabaseHas('role_permission', [
            'role_id' => $role->id,
            'permission_id' => $permission1->id,
        ]);
        $this->assertDatabaseHas('role_permission', [
            'role_id' => $role->id,
            'permission_id' => $permission2->id,
        ]);
    }

    /**
     * Test that Role can be detached from permissions
     */
    public function test_can_be_detached_from_permissions(): void
    {
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();
        
        $role->permissions()->attach([$permission1->id, $permission2->id]);
        
        // Detach one permission
        $role->permissions()->detach($permission1->id);
        
        $this->assertCount(1, $role->fresh()->permissions);
        $this->assertFalse($role->fresh()->permissions->contains($permission1));
        $this->assertTrue($role->fresh()->permissions->contains($permission2));
        
        // Verify pivot table
        $this->assertDatabaseMissing('role_permission', [
            'role_id' => $role->id,
            'permission_id' => $permission1->id,
        ]);
        $this->assertDatabaseHas('role_permission', [
            'role_id' => $role->id,
            'permission_id' => $permission2->id,
        ]);
    }

    /**
     * Test that Role can sync permissions
     */
    public function test_can_sync_permissions(): void
    {
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();
        $permission3 = Permission::factory()->create();
        
        // Initial attachment
        $role->permissions()->attach([$permission1->id, $permission2->id]);
        
        // Sync with different permissions
        $role->permissions()->sync([$permission2->id, $permission3->id]);
        
        $this->assertCount(2, $role->fresh()->permissions);
        $this->assertFalse($role->fresh()->permissions->contains($permission1));
        $this->assertTrue($role->fresh()->permissions->contains($permission2));
        $this->assertTrue($role->fresh()->permissions->contains($permission3));
    }

    /**
     * Test that Role can sync permissions without detaching
     */
    public function test_can_sync_permissions_without_detaching(): void
    {
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();
        $permission3 = Permission::factory()->create();
        
        // Initial attachment
        $role->permissions()->attach($permission1->id);
        
        // Sync without detaching
        $role->permissions()->syncWithoutDetaching([$permission2->id, $permission3->id]);
        
        $this->assertCount(3, $role->fresh()->permissions);
        $this->assertTrue($role->fresh()->permissions->contains($permission1));
        $this->assertTrue($role->fresh()->permissions->contains($permission2));
        $this->assertTrue($role->fresh()->permissions->contains($permission3));
    }

    /**
     * Test that Role permissions relationship uses correct pivot table
     */
    public function test_permissions_relationship_uses_correct_pivot_table(): void
    {
        $role = Role::factory()->create();
        
        $pivotTable = $role->permissions()->getTable();
        
        $this->assertEquals('role_permission', $pivotTable);
    }

    /**
     * Test that Role can have both users and permissions
     */
    public function test_can_have_both_users_and_permissions(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        $permission = Permission::factory()->create();
        
        $role->users()->attach($user->id);
        $role->permissions()->attach($permission->id);
        
        $this->assertCount(1, $role->users);
        $this->assertCount(1, $role->permissions);
        $this->assertTrue($role->users->contains($user));
        $this->assertTrue($role->permissions->contains($permission));
    }

    /**
     * Test that Role can be deleted
     */
    public function test_can_be_deleted(): void
    {
        $role = Role::factory()->create();
        $roleId = $role->id;
        
        $role->delete();
        
        $this->assertDatabaseMissing('roles', [
            'id' => $roleId,
        ]);
    }

    /**
     * Test that deleting Role removes pivot records
     */
    public function test_deleting_role_removes_pivot_records(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        $permission = Permission::factory()->create();
        
        $role->users()->attach($user->id);
        $role->permissions()->attach($permission->id);
        
        // Verify pivot records exist
        $this->assertDatabaseHas('user_role', [
            'role_id' => $role->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('role_permission', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
        
        $roleId = $role->id;
        $role->delete();
        
        // Verify pivot records are removed (depending on database constraints)
        // Note: This behavior depends on your migration's onDelete settings
        $this->assertDatabaseMissing('user_role', [
            'role_id' => $roleId,
        ]);
        $this->assertDatabaseMissing('role_permission', [
            'role_id' => $roleId,
        ]);
    }

    /**
     * Test Role timestamps
     */
    public function test_has_timestamps(): void
    {
        $role = Role::factory()->create();
        
        $this->assertNotNull($role->created_at);
        $this->assertNotNull($role->updated_at);
    }

    /**
     * Test Role model instance
     */
    public function test_model_instance(): void
    {
        $role = new Role();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $role);
    }

    /**
     * Test Role can use firstOrCreate
     */
    public function test_can_use_first_or_create(): void
    {
        // Create first role
        $role1 = Role::firstOrCreate(['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        
        // Try to create again - should return existing
        $role2 = Role::firstOrCreate(['name' => 'admin']);
        
        $this->assertEquals($role1->id, $role2->id);
        $this->assertCount(1, Role::where('name', 'admin')->get());
    }

    /**
     * Test Role users relationship eager loading
     */
    public function test_can_eager_load_users(): void
    {
        $role = Role::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $role->users()->attach([$user1->id, $user2->id]);
        
        $loadedRole = Role::with('users')->find($role->id);
        
        $this->assertTrue($loadedRole->relationLoaded('users'));
        $this->assertCount(2, $loadedRole->users);
    }

    /**
     * Test Role permissions relationship eager loading
     */
    public function test_can_eager_load_permissions(): void
    {
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();
        
        $role->permissions()->attach([$permission1->id, $permission2->id]);
        
        $loadedRole = Role::with('permissions')->find($role->id);
        
        $this->assertTrue($loadedRole->relationLoaded('permissions'));
        $this->assertCount(2, $loadedRole->permissions);
    }
}