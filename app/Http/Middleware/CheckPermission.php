<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Permission Middleware
 *
 * OOAD: Middleware for permission-based access control
 * Usage: Route::middleware(['auth:sanctum', 'permission:' . Permission::USERS_CREATE->value])
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param string $permissionSlug Permission slug from route parameter
     */
    public function handle(Request $request, Closure $next, string $permissionSlug): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status_code' => 401,
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Validate permission exists in enum
        if (!Permission::exists($permissionSlug)) {
            return response()->json([
                'status_code' => 500,
                'status' => 'error',
                'message' => "Invalid permission '{$permissionSlug}' configured.",
            ], 500);
        }

        // Check permission
        if (!$user->hasPermissionSlug($permissionSlug)) {
            return response()->json([
                'status_code' => 403,
                'status' => 'error',
                'message' => 'Forbidden. You do not have the required permission.',
            ], 403);
        }

        return $next($request);
    }
}
