<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Super Admin Role Middleware
 * 
 * Security: Only users with 'super-admin' role can access protected routes.
 * OWASP A01:2021 - Broken Access Control: Strict role verification.
 * OWASP A07:2021 - Security Logging: Failed attempts logged via activity logger.
 */
class EnsureSuperAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // OWASP A01:2021 - Verify authentication
        if (!$user) {
            return response()->json([
                'status_code' => 401,
                'status' => 'error',
                'message' => 'Unauthenticated.',
                'errors' => null,
            ], 401);
        }

        // OWASP A01:2021 - Verify super admin role
        if ($user->role?->slug !== Role::SUPER_ADMIN_SLUG) {
            // Log unauthorized access attempt for security monitoring
            if (app()->bound('activity.logger')) {
                app('activity.logger')->log(
                    $user,
                    "Unauthorized super admin access attempt to: {$request->path()}",
                    $request->ip()
                );
            }

            return response()->json([
                'status_code' => 403,
                'status' => 'error',
                'message' => 'Forbidden. Super Admin access required.',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
