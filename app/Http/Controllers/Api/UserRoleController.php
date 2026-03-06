<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\AssignRoleRequest;
use App\Http\Resources\UserResource;
use App\Jobs\Admin\SendRoleAssignmentAlertJob;
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
        $fullName = trim("{$user->first_name} {$user->last_name}") ?: $user->username;
        $this->activityLogger->log(
            $request->user(),
            "Assigned role {$roleName} to user {$fullName}",
            $request->ip()
        );

        // Alert Telegram with role assignment details
        $admin = $request->user();
        $adminName = trim("{$admin?->first_name} {$admin?->last_name}") ?: $admin?->username ?? 'Unknown';
        $adminEmail = $admin?->email ?? '-';
        $actor = "{$adminName} ({$adminEmail})";

        $targetName = $fullName ?: '-';
        $roleLabel = $roleName ?: 'Unknown';

        SendRoleAssignmentAlertJob::dispatch([
            'admin' => $actor,
            'user' => $targetName,
            'role' => $roleLabel,
        ]);

        return $this->successResponse(
            (new UserResource($user->fresh('role')))->toArray(request()),
            'Role assigned.'
        );
    }
}
