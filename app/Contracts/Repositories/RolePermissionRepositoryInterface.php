<?php

namespace App\Contracts\Repositories;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * Role Permission Repository Interface
 *
 * OOAD: Repository Pattern for many-to-many relationship management
 */
interface RolePermissionRepositoryInterface
{
    /**
     * Get all permissions for a role.
     *
     * @return Collection<int, Permission>
     */
    public function getPermissionsForRole(Role $role): Collection;

    /**
     * Get all roles that have a specific permission.
     *
     * @return Collection<int, Role>
     */
    public function getRolesWithPermission(Permission $permission): Collection;

    /**
     * Check if role has a specific permission.
     */
    public function roleHasPermission(Role $role, string $permissionSlug): bool;

    /**
     * Sync permissions for a role.
     *
     * @param array<int> $permissionIds
     * @return array<string, array<int>>
     */
    public function syncPermissions(Role $role, array $permissionIds): array;

    /**
     * Detach a specific permission from a role.
     */
    public function detachPermission(Role $role, Permission $permission): bool;

    /**
     * Detach all permissions from a role.
     */
    public function detachAllPermissions(Role $role): int;

    /**
     * Count permissions for a role.
     */
    public function countPermissions(Role $role): int;
}
