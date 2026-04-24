<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CliAuth\ExchangeCliAuthRequest;
use App\Http\Requests\CliAuth\StartCliAuthRequest;
use App\Http\Resources\CliAuth\CliAuthExchangeResource;
use App\Http\Resources\CliAuth\CliAuthMeResource;
use App\Http\Resources\CliAuth\CliAuthStartResource;
use App\Services\CliAuthService;
use Illuminate\Http\Request;

class CliAuthController extends Controller
{
    public function __construct(private CliAuthService $cliAuthService) {}

    public function start(StartCliAuthRequest $request)
    {
        $payload = $this->cliAuthService->start($request->validated(), $request);

        return response()->json([
            'success' => true,
            'data' => new CliAuthStartResource($payload),
        ], 201);
    }

    public function exchange(ExchangeCliAuthRequest $request)
    {
        $result = $this->cliAuthService->exchange($request->validated()['device_code']);

        if (! $result['ok']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'data' => new CliAuthExchangeResource($result['data']),
        ]);
    }

    public function me(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        return response()->json([
            'success' => true,
            'data' => new CliAuthMeResource([
                'user' => $request->user()->load('role'),
                'token_name' => $token?->name,
                'abilities' => $token?->abilities ?? [],
                'expires_at' => $token?->expires_at,
            ]),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'CLI token revoked.',
        ]);
    }
}
