<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Any Permission Middleware
 *
 * OOAD: Middleware for checking if user has ANY of the specified permissions
 * Usage: Route::middleware(['auth:sanctum', 'any_permission:' . Permission::USERS_CREATE->value . ',' . Permission::USERS_EDIT->value])
 */
class CheckAnyPermission
{
    /**
     * Handle an incoming request.
     *
     * @param string ...$permissionSlugs Comma-separated permission slugs
     */
    public function handle(Request $request, Closure $next, string ...$permissionSlugs): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status_code' => 401,
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Convert slugs to Permission enums
        $permissions = [];
        foreach ($permissionSlugs as $slug) {
            $permission = Permission::tryFrom($slug);
            if ($permission === null) {
                return response()->json([
                    'status_code' => 500,
                    'status' => 'error',
                    'message' => "Invalid permission '{$slug}' configured.",
                ], 500);
            }
            $permissions[] = $permission;
        }

        // Check if user has any of the permissions
        if (!$user->hasAnyPermission($permissions)) {
            return response()->json([
                'status_code' => 403,
                'status' => 'error',
                'message' => 'Forbidden. You do not have any of the required permissions.',
            ], 403);
        }

        return $next($request);
    }
}
