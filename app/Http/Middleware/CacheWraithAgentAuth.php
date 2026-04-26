<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheWraithAgentAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('services.cachewraith.agent_token', '');
        $providedToken = (string) $request->bearerToken();

        if ($configuredToken === '' || $providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'status_code' => 401,
                'status' => 'error',
                'message' => 'Unauthenticated.',
                'errors' => null,
            ], 401);
        }

        return $next($request);
    }
}
