<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Allow both admin and super-admin roles
        $allowedRoles = [Role::ADMIN_SLUG, Role::SUPER_ADMIN_SLUG];

        if (! $user || ! in_array($user->role?->slug, $allowedRoles, true)) {
            return response()->json([
                'status_code' => 403,
                'status' => 'error',
                'message' => 'Forbidden.',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
