<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\AssignRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Traits\ApiResponseTrait;

class UserRoleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ActivityLogger $activityLogger)
    {
    }

    public function assign(AssignRoleRequest $request, User $user)
    {
        $data = $request->validated();

        $user->role_id = $data['role_id'];
        $user->save();

        $roleName = $user->role?->name ?? '';
        $this->activityLogger->log(
            $request->user(),
            "Assigned role {$roleName} to user #{$user->id}",
            $request->ip()
        );

        return $this->successResponse(
            (new UserResource($user->fresh('role')))->toArray(request()),
            'Role assigned.'
        );
    }
}
