<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOrPartnerRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $roleSlug = $request->user()?->role?->slug;

        if (!in_array($roleSlug, [Role::ADMIN_SLUG, Role::PARTNER_SLUG], true)) {
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
