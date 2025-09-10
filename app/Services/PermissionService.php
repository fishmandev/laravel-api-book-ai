<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class PermissionService
{
    /**
     * Define all application gates from database
     */
    public static function defineGates(): void
    {
        // Skip gate definition if permissions table doesn't exist (testing environments)
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissions = Permission::pluck('name');

        foreach ($permissions as $permission) {
            Gate::define($permission, function ($user) use ($permission) {
                /** @var User $user */
                return $user->hasPermission($permission);
            });
        }
    }
}
