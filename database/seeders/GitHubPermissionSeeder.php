<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionRegistrar;
use Illuminate\Database\Seeder;

class GitHubPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->syncPermissions();

        $slugs = [
            PermissionEnum::GITHUB_REPOSITORIES_VIEW->value,
            PermissionEnum::GITHUB_REPOSITORIES_CREATE->value,
            PermissionEnum::GITHUB_REPOSITORIES_SYNC->value,
        ];

        $permissionIds = Permission::query()
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->all();

        Role::query()
            ->whereIn('slug', [Role::ADMIN_SLUG, Role::SUPER_ADMIN_SLUG])
            ->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching($permissionIds));
    }
}
