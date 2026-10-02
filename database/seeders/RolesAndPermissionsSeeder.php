<?php

namespace Database\Seeders;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::values() as $permission) {
            PermissionModel::findOrCreate($permission, 'web');
        }
        foreach (Role::permissionMap() as $role => $permissions) {
            RoleModel::findOrCreate($role, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
