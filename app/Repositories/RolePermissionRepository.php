<?php

namespace App\Repositories;

use App\Contracts\Repositories\RolePermissionRepositoryInterface;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * Role Permission Repository
 *
 * OOAD: Repository Pattern for many-to-many relationships
 * Clean Architecture: Data access abstraction
 */
class RolePermissionRepository implements RolePermissionRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPermissionsForRole(Role $role): Collection
    {
        return $role->permissions()
            ->orderBy('name')
            ->get(['permissions.id', 'permissions.name', 'permissions.slug', 'permissions.description']);
    }

    /**
     * {@inheritDoc}
     */
    public function getRolesWithPermission(Permission $permission): Collection
    {
        return $permission->roles()
            ->orderBy('name')
            ->get(['roles.id', 'roles.name', 'roles.slug', 'roles.description']);
    }

    /**
     * {@inheritDoc}
     */
    public function roleHasPermission(Role $role, string $permissionSlug): bool
    {
        return $role->permissions()
            ->where('slug', $permissionSlug)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function syncPermissions(Role $role, array $permissionIds): array
    {
        // Use syncWithoutDetaching to ADD permissions without removing existing ones
        $role->permissions()->syncWithoutDetaching($permissionIds);

        return [
            'attached' => $permissionIds,
            'detached' => [],
            'updated' => [],
        ];
    }

    /**
     * {@inheritDoc}
     * Set exact permissions (replaces existing).
     */
    public function setPermissions(Role $role, array $permissionIds): array
    {
        return $role->permissions()->sync($permissionIds);
    }

    /**
     * {@inheritDoc}
     */
    public function detachPermission(Role $role, Permission $permission): bool
    {
        $role->permissions()->detach($permission->id);
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function detachAllPermissions(Role $role): int
    {
        $count = $role->permissions()->count();
        $role->permissions()->detach();
        return $count;
    }

    /**
     * {@inheritDoc}
     */
    public function countPermissions(Role $role): int
    {
        return $role->permissions()->count();
    }
}
