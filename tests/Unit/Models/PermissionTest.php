<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Permission model uses correct table
     */
    public function test_uses_correct_table(): void
    {
        $permission = new Permission();
        
        $this->assertEquals('permissions', $permission->getTable());
    }

    /**
     * Test that Permission has correct fillable attributes
     */
    public function test_has_correct_fillable_attributes(): void
    {
        $permission = new Permission();
        
        $this->assertEquals(['name'], $permission->getFillable());
    }

    /**
     * Test that Permission can be created with factory
     */
    public function test_can_be_created_with_factory(): void
    {
        $permission = Permission::factory()->create();
        
        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => $permission->name,
        ]);
    }

    /**
     * Test that Permission can be created with mass assignment
     */
    public function test_can_be_created_with_mass_assignment(): void
    {
        $permissionData = [
            'name' => 'test_permission',
        ];
        
        $permission = Permission::create($permissionData);
        
        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals('test_permission', $permission->name);
        $this->assertDatabaseHas('permissions', $permissionData);
    }

    /**
     * Test that Permission name can be updated
     */
    public function test_name_can_be_updated(): void
    {
        $permission = Permission::factory()->create(['name' => 'original_permission']);
        
        $permission->update(['name' => 'updated_permission']);
        
        $this->assertEquals('updated_permission', $permission->fresh()->name);
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'updated_permission',
        ]);
    }

    /**
     * Test that Permission has roles relationship
     */
    public function test_has_roles_relationship(): void
    {
        $permission = Permission::factory()->create();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $permission->roles()
        );
    }

    /**
     * Test that Permission can be attached to roles
     */
    public function test_can_be_attached_to_roles(): void
    {
        $permission = Permission::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();
        
        $permission->roles()->attach([$role1->id, $role2->id]);
        
        $this->assertCount(2, $permission->roles);
        $this->assertTrue($permission->roles->contains($role1));
        $this->assertTrue($permission->roles->contains($role2));
        
        // Test the pivot table
        $this->assertDatabaseHas('role_permission', [
            'permission_id' => $permission->id,
            'role_id' => $role1->id,
        ]);
        $this->assertDatabaseHas('role_permission', [
            'permission_id' => $permission->id,
            'role_id' => $role2->id,
        ]);
    }

    /**
     * Test that Permission can be detached from roles
     */
    public function test_can_be_detached_from_roles(): void
    {
        $permission = Permission::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();
        
        $permission->roles()->attach([$role1->id, $role2->id]);
        
        // Detach one role
        $permission->roles()->detach($role1->id);
        
        $this->assertCount(1, $permission->fresh()->roles);
        $this->assertFalse($permission->fresh()->roles->contains($role1));
        $this->assertTrue($permission->fresh()->roles->contains($role2));
        
        // Verify pivot table
        $this->assertDatabaseMissing('role_permission', [
            'permission_id' => $permission->id,
            'role_id' => $role1->id,
        ]);
        $this->assertDatabaseHas('role_permission', [
            'permission_id' => $permission->id,
            'role_id' => $role2->id,
        ]);
    }

    /**
     * Test that Permission can sync roles
     */
    public function test_can_sync_roles(): void
    {
        $permission = Permission::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();
        $role3 = Role::factory()->create();
        
        // Initial attachment
        $permission->roles()->attach([$role1->id, $role2->id]);
        
        // Sync with different roles
        $permission->roles()->sync([$role2->id, $role3->id]);
        
        $this->assertCount(2, $permission->fresh()->roles);
        $this->assertFalse($permission->fresh()->roles->contains($role1));
        $this->assertTrue($permission->fresh()->roles->contains($role2));
        $this->assertTrue($permission->fresh()->roles->contains($role3));
    }

    /**
     * Test that Permission roles relationship uses correct pivot table
     */
    public function test_roles_relationship_uses_correct_pivot_table(): void
    {
        $permission = Permission::factory()->create();
        
        $pivotTable = $permission->roles()->getTable();
        
        $this->assertEquals('role_permission', $pivotTable);
    }

    /**
     * Test that Permission can be deleted
     */
    public function test_can_be_deleted(): void
    {
        $permission = Permission::factory()->create();
        $permissionId = $permission->id;
        
        $permission->delete();
        
        $this->assertDatabaseMissing('permissions', [
            'id' => $permissionId,
        ]);
    }

    /**
     * Test that deleting Permission removes pivot records
     */
    public function test_deleting_permission_removes_pivot_records(): void
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();
        
        $permission->roles()->attach($role->id);
        
        // Verify pivot record exists
        $this->assertDatabaseHas('role_permission', [
            'permission_id' => $permission->id,
            'role_id' => $role->id,
        ]);
        
        $permissionId = $permission->id;
        $permission->delete();
        
        // Verify pivot record is removed (depending on database constraints)
        // Note: This behavior depends on your migration's onDelete settings
        $this->assertDatabaseMissing('role_permission', [
            'permission_id' => $permissionId,
        ]);
    }

    /**
     * Test Permission timestamps
     */
    public function test_has_timestamps(): void
    {
        $permission = Permission::factory()->create();
        
        $this->assertNotNull($permission->created_at);
        $this->assertNotNull($permission->updated_at);
    }

    /**
     * Test Permission model instance
     */
    public function test_model_instance(): void
    {
        $permission = new Permission();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $permission);
    }
}