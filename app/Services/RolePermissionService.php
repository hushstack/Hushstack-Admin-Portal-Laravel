<?php

namespace App\Services;

use App\Contracts\Repositories\PermissionRepositoryInterface;
use App\Contracts\Repositories\RolePermissionRepositoryInterface;
use App\Contracts\Services\RolePermissionServiceInterface;
use App\DTOs\Permission\AssignPermissionsData;
use App\Exceptions\InvalidPermissionException;
use App\Exceptions\PermissionNotAssignedException;
use App\Exceptions\ProtectedRoleException;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Role Permission Service
 *
 * OOAD: Service Layer for role-permission assignment logic
 * Clean Architecture: Transactional business operations
 */
class RolePermissionService implements RolePermissionServiceInterface
{
    public function __construct(
        private readonly RolePermissionRepositoryInterface $rolePermissionRepo,
        private readonly PermissionRepositoryInterface $permissionRepo,
        private readonly CacheService $cacheService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getRolePermissions(Role $role): Collection
    {
        $cacheKey = "role:{$role->id}:permissions";

        return $this->cacheService->remember(
            $cacheKey,
            now()->addMinutes(10),
            fn () => $this->rolePermissionRepo->getPermissionsForRole($role)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getRolesWithPermission(Permission $permission): Collection
    {
        return $this->rolePermissionRepo->getRolesWithPermission($permission);
    }

    /**
     * {@inheritDoc}
     */
    public function hasPermission(Role $role, string $permissionSlug): bool
    {
        return $this->rolePermissionRepo->roleHasPermission($role, $permissionSlug);
    }

    /**
     * {@inheritDoc}
     */
    public function assignPermissions(Role $role, AssignPermissionsData $data): array
    {
        if ($role->isSuperAdmin()) {
            throw ProtectedRoleException::superAdmin();
        }

        if ($data->isEmpty()) {
            throw InvalidPermissionException::idsNotFound([]);
        }

        // Get sanitized permission IDs
        $permissionIds = $data->getSanitizedIds();

        // Validate all permissions exist
        $this->validatePermissionIds($permissionIds);

        return DB::transaction(function () use ($role, $permissionIds) {
            $syncResult = $this->rolePermissionRepo->syncPermissions($role, $permissionIds);

            $this->cacheService->forget("role:{$role->id}:permissions");
            $this->cacheService->forget('permissions:all');

            return [
                'attached' => $syncResult['attached'] ?? [],
                'detached' => $syncResult['detached'] ?? [],
                'updated' => $syncResult['updated'] ?? [],
            ];
        });
    }

    /**
     * {@inheritDoc}
     */
    public function revokePermission(Role $role, Permission $permission): void
    {
        if (!$this->hasPermission($role, $permission->slug)) {
            throw PermissionNotAssignedException::forPermissionAndRole(
                $permission->name,
                $role->name
            );
        }

        DB::transaction(function () use ($role, $permission) {
            $this->rolePermissionRepo->detachPermission($role, $permission);

            $this->cacheService->forget("role:{$role->id}:permissions");
        });
    }

    /**
     * {@inheritDoc}
     */
    public function revokeAllPermissions(Role $role): int
    {
        if ($role->isSuperAdmin()) {
            throw ProtectedRoleException::superAdmin();
        }

        $count = $this->rolePermissionRepo->countPermissions($role);

        if ($count === 0) {
            return 0;
        }

        return DB::transaction(function () use ($role, $count) {
            $this->rolePermissionRepo->detachAllPermissions($role);

            $this->cacheService->forget("role:{$role->id}:permissions");

            return $count;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function validatePermissionIds(array $permissionIds): void
    {
        $existingPermissions = $this->permissionRepo->getByIds($permissionIds);
        $existingIds = $existingPermissions->pluck('id')->toArray();

        $invalidIds = array_diff($permissionIds, $existingIds);

        if (!empty($invalidIds)) {
            throw InvalidPermissionException::idsNotFound($invalidIds);
        }
    }
}
