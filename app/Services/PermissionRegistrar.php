<?php

namespace App\Services;

use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Permission Registrar
 *
 * OOAD: Service to sync hardcoded enum permissions to database
 * Clean Architecture: Single source of truth (enum) drives database state
 */
class PermissionRegistrar
{
    /**
     * Sync all hardcoded permissions to database.
     * Creates new, updates existing, removes obsolete.
     */
    public function syncPermissions(): void
    {
        $hardcoded = PermissionEnum::toSeedArray();
        $hardcodedSlugs = array_column($hardcoded, 'slug');

        DB::transaction(function () use ($hardcoded, $hardcodedSlugs) {
            // 1. Create or update permissions from enum
            foreach ($hardcoded as $permissionData) {
                Permission::updateOrCreate(
                    ['slug' => $permissionData['slug']],
                    [
                        'name' => $permissionData['name'],
                        'description' => $permissionData['description'],
                    ]
                );
            }

            // 2. Remove permissions from DB that are no longer in enum
            // But only if they have no roles assigned (safety)
            $obsoletePermissions = Permission::whereNotIn('slug', $hardcodedSlugs)
                ->doesntHave('roles')
                ->get();

            foreach ($obsoletePermissions as $obsolete) {
                Log::info("Removing obsolete permission: {$obsolete->slug}");
                $obsolete->delete();
            }

            // 3. Log warnings for obsolete permissions still in use
            $stillInUse = Permission::whereNotIn('slug', $hardcodedSlugs)
                ->has('roles')
                ->pluck('slug')
                ->toArray();

            if (!empty($stillInUse)) {
                Log::warning(
                    'Obsolete permissions still assigned to roles: ' . implode(', ', $stillInUse)
                );
            }
        });
    }

    /**
     * Get permission model by enum.
     */
    public function getPermissionModel(PermissionEnum $permission): ?Permission
    {
        return Permission::where('slug', $permission->value)->first();
    }

    /**
     * Check if permission is registered in database.
     */
    public function isRegistered(PermissionEnum $permission): bool
    {
        return Permission::where('slug', $permission->value)->exists();
    }

    /**
     * Get registration status for all permissions.
     *
     * @return array<string, bool>
     */
    public function getRegistrationStatus(): array
    {
        $slugs = PermissionEnum::allSlugs();
        $existing = Permission::whereIn('slug', $slugs)
            ->pluck('slug')
            ->toArray();

        return array_reduce(
            $slugs,
            fn (array $carry, string $slug) => [...$carry, $slug => in_array($slug, $existing, true)],
            []
        );
    }
}
