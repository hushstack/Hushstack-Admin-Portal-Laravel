<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\AssignRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponseTrait;

class UserRoleController extends Controller
{
    use ApiResponseTrait;

    public function assign(AssignRoleRequest $request, User $user)
    {
        $data = $request->validated();

        $user->role_id = $data['role_id'];
        $user->save();

        return $this->successResponse(
            (new UserResource($user->fresh('role')))->toArray(request()),
            'Role assigned.'
        );
    }
}
