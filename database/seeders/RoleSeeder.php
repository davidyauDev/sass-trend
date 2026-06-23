<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Services\Users\UserRoleCatalog;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UserRoleCatalog::definitions() as $definition) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_system' => $definition['is_system'],
                ],
            );

            $permissionIds = Permission::query()
                ->whereIn('slug', UserRoleCatalog::basePermissionSlugs()[$definition['slug']] ?? [])
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
