<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Services\Users\UserPermissionCatalog;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UserPermissionCatalog::definitions() as $definition) {
            Permission::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'group' => $definition['group'],
                    'description' => $definition['description'],
                ],
            );
        }
    }
}
