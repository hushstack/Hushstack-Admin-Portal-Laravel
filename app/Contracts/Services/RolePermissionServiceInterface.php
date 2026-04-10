<?php

namespace App\Contracts\Services;

use App\DTOs\Permission\AssignPermissionsData;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

/**
 * Role Permission Service Interface
 *
 * OOAD: Service Layer for role-permission assignment business logic
 */
interface RolePermissionServiceInterface
{
    /**
     * Get permissions for a role.
     *
     * @return Collection<int, Permission>
     */
    public function getRolePermissions(Role $role): Collection;

    /**
     * Get roles with a specific permission.
     *
     * @return Collection<int, Role>
     */
    public function getRolesWithPermission(Permission $permission): Collection;

    /**
     * Check if role has permission.
     */
    public function hasPermission(Role $role, string $permissionSlug): bool;

    /**
     * Assign permissions to role.
     *
     * @return array<string, mixed>
     * @throws \App\Exceptions\InvalidPermissionException
     * @throws \App\Exceptions\ProtectedRoleException
     */
    public function assignPermissions(Role $role, AssignPermissionsData $data): array;

    /**
     * Revoke permission from role.
     *
     * @throws \App\Exceptions\PermissionNotAssignedException
     */
    public function revokePermission(Role $role, Permission $permission): void;

    /**
     * Revoke all permissions from role.
     *
     * @throws \App\Exceptions\ProtectedRoleException
     */
    public function revokeAllPermissions(Role $role): int;

    /**
     * Validate permission IDs exist.
     *
     * @param array<int> $permissionIds
     * @throws \App\Exceptions\InvalidPermissionException
     */
    public function validatePermissionIds(array $permissionIds): void;
}
