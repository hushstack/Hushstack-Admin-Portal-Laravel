<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCatalogUserRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->user()?->role?->slug;

        if (! in_array($slug, [Role::ADMIN_SLUG, Role::PARTNER_SLUG, Role::USER_SLUG], true)) {
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
