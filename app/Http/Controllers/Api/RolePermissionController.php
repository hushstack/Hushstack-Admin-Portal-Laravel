<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\RolePermissionServiceInterface;
use App\DTOs\Permission\AssignPermissionsData;
use App\Exceptions\InvalidPermissionException;
use App\Exceptions\PermissionNotAssignedException;
use App\Exceptions\ProtectedRoleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\AssignPermissionRequest;
use App\Http\Requests\Permission\RevokePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogger;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Throwable;

/**
 * Role Permission Controller
 *
 * OOAD: Controller delegates to Service Layer
 * Clean Architecture: Thin controller, fat service
 * Security: Only Super Admin can assign/revoke permissions.
 */
class RolePermissionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly RolePermissionServiceInterface $rolePermissionService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Get all permissions for a role.
     *
     * Performance: Service layer handles caching.
     */
    public function index(Request $request, Role $role)
    {
        $permissions = $this->rolePermissionService->getRolePermissions($role);

        return $this->successResponse(
            PermissionResource::collection($permissions),
            "Permissions for role {$role->name} loaded."
        );
    }

    /**
     * Assign permissions to a role.
     *
     * OWASP A03:2021 - DTO transfers validated data.
     * Clean Architecture: Service handles transactions.
     */
    public function assign(AssignPermissionRequest $request, Role $role)
    {
        try {
            $data = AssignPermissionsData::fromRequest($request);
            $result = $this->rolePermissionService->assignPermissions($role, $data);

            $attached = count($result['attached']);
            $detached = count($result['detached']);

            $this->activityLogger->log(
                $request->user(),
                "Updated permissions for role {$role->name}: +{$attached} added, -{$detached} removed",
                $request->ip()
            );

            return $this->successResponse(
                [
                    'role' => new RoleResource($role),
                    'permissions' => PermissionResource::collection($role->permissions()->get()),
                    'sync_result' => $result,
                ],
                "Permissions assigned to role {$role->name}."
            );
        } catch (InvalidPermissionException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), null, 'INVALID_PERMISSION');
        } catch (ProtectedRoleException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), null, 'PROTECTED_ROLE');
        } catch (Throwable $e) {
            report($e);
            return $this->serverErrorResponse(
                'Failed to assign permissions: ' . $e->getMessage(),
                'ASSIGN_PERMISSION_FAILED'
            );
        }
    }

    /**
     * Revoke specific permissions from a role.
     *
     * Body: { "permission_ids": [1, 2, 3] }
     * Clean Architecture: Service layer handles transactions.
     */
    public function revoke(RevokePermissionRequest $request, Role $role)
    {
        try {
            $permissionIds = $request->validated('permission_ids', []);
            $results = [
                'revoked' => [],
                'not_assigned' => [],
            ];

            foreach ($permissionIds as $permissionId) {
                $permission = Permission::find($permissionId);

                if (!$permission) {
                    continue;
                }

                try {
                    $this->rolePermissionService->revokePermission($role, $permission);
                    $results['revoked'][] = [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'slug' => $permission->slug,
                    ];
                } catch (PermissionNotAssignedException $e) {
                    $results['not_assigned'][] = [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'slug' => $permission->slug,
                    ];
                }
            }

            $revokedCount = count($results['revoked']);

            if ($revokedCount > 0) {
                $this->activityLogger->log(
                    $request->user(),
                    "Revoked {$revokedCount} permissions from role {$role->name}",
                    $request->ip()
                );
            }

            return $this->successResponse(
                [
                    'role' => new RoleResource($role),
                    'results' => $results,
                ],
                "{$revokedCount} permission(s) revoked from role {$role->name}."
            );
        } catch (Throwable $e) {
            report($e);
            return $this->serverErrorResponse(
                'Failed to revoke permissions: ' . $e->getMessage(),
                'REVOKE_PERMISSION_FAILED'
            );
        }
    }

    /**
     * Revoke all permissions from a role.
     *
     * Clean Architecture: Service layer handles business rules.
     */
    public function revokeAll(Request $request, Role $role)
    {
        try {
            $count = $this->rolePermissionService->revokeAllPermissions($role);

            if ($count === 0) {
                return $this->errorResponse('Role has no permissions to revoke.', 422, null, 'NO_PERMISSIONS_TO_REVOKE');
            }

            $this->activityLogger->log(
                $request->user(),
                "Revoked all {$count} permissions from role {$role->name}",
                $request->ip()
            );

            return $this->successResponse(
                null,
                "All {$count} permissions revoked from role {$role->name}."
            );
        } catch (ProtectedRoleException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode(), null, 'PROTECTED_ROLE');
        } catch (Throwable $e) {
            report($e);
            return $this->serverErrorResponse(
                'Failed to revoke permissions: ' . $e->getMessage(),
                'REVOKE_ALL_PERMISSIONS_FAILED'
            );
        }
    }

    /**
     * Get roles that have a specific permission.
     *
     * Performance: Service layer handles query optimization.
     */
    public function rolesWithPermission(Request $request, Permission $permission)
    {
        $roles = $this->rolePermissionService->getRolesWithPermission($permission);

        return $this->successResponse(
            RoleResource::collection($roles),
            "Roles with permission {$permission->name} loaded."
        );
    }
}
