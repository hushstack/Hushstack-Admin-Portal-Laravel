<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\PermissionServiceInterface;
use App\DTOs\Permission\CreatePermissionData;
use App\DTOs\Permission\UpdatePermissionData;
use App\Exceptions\DuplicatePermissionException;
use App\Exceptions\PermissionInUseException;
use App\Exceptions\PermissionNotFoundException;
use App\Exceptions\ProtectedPermissionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Services\ActivityLogger;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Throwable;

/**
 * Permission Controller
 *
 * OOAD: Controller delegates to Service Layer
 * Clean Architecture: Thin controller, fat service
 * Security: All endpoints require Super Admin role.
 */
class PermissionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly PermissionServiceInterface $permissionService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * List all permissions.
     *
     * Performance: Service layer handles caching.
     * Rate Limit: 60 requests per minute.
     */
    public function index(Request $request)
    {
        $permissions = $this->permissionService->getAll();

        $this->activityLogger->log(
            $request->user(),
            'Listed all permissions',
            $request->ip()
        );

        return $this->successResponse(
            PermissionResource::collection($permissions),
            'Permissions loaded.'
        );
    }

    /**
     * Store a new permission.
     *
     * OOASP A03:2021 - Input validation via FormRequest.
     * Clean Architecture: DTO transfers data to service layer.
     */
    public function store(StorePermissionRequest $request)
    {
        try {
            $data = CreatePermissionData::fromRequest($request);
            $permission = $this->permissionService->create($data);

            $this->activityLogger->log(
                $request->user(),
                "Created permission {$permission->name}",
                $request->ip()
            );

            return $this->successResponse(
                new PermissionResource($permission),
                'Permission created.',
                201
            );
        } catch (DuplicatePermissionException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (Throwable $e) {
            report($e);
            return $this->errorResponse('Failed to create permission.', 500);
        }
    }

    /**
     * Display a specific permission.
     *
     * OOAD: Route model binding with service fallback.
     */
    public function show(Request $request, Permission $permission)
    {
        $this->activityLogger->log(
            $request->user(),
            "Viewed permission {$permission->name}",
            $request->ip()
        );

        return $this->successResponse(
            new PermissionResource($permission),
            'Permission loaded.'
        );
    }

    /**
     * Update a permission.
     *
     * OWASP A03:2021 - Input validation via FormRequest.
     * Clean Architecture: Service layer handles business rules.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        try {
            $data = UpdatePermissionData::fromRequest($request);
            $updatedPermission = $this->permissionService->update($permission->id, $data);

            $this->activityLogger->log(
                $request->user(),
                "Updated permission {$updatedPermission->name}",
                $request->ip()
            );

            return $this->successResponse(
                new PermissionResource($updatedPermission),
                'Permission updated.'
            );
        } catch (PermissionNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (DuplicatePermissionException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (ProtectedPermissionException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (Throwable $e) {
            report($e);
            return $this->errorResponse('Failed to update permission.', 500);
        }
    }

    /**
     * Remove a permission.
     *
     * OWASP A01:2021 - Service layer verifies no dependencies.
     * Clean Architecture: Domain exceptions for business rules.
     */
    public function destroy(Request $request, Permission $permission)
    {
        try {
            $permissionName = $permission->name;
            $this->permissionService->delete($permission->id);

            $this->activityLogger->log(
                $request->user(),
                "Deleted permission {$permissionName}",
                $request->ip()
            );

            return $this->successResponse(null, 'Permission deleted.');
        } catch (PermissionNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (ProtectedPermissionException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (PermissionInUseException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (Throwable $e) {
            report($e);
            return $this->errorResponse('Failed to delete permission.', 500);
        }
    }
}
