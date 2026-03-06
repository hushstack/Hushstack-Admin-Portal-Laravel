<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleCallbackRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\GoogleAuthService;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function __construct(
        private GoogleAuthService $google,
        private AuthService $auth
    ) {}

    public function redirect(Request $request)
    {
        return $this->google->redirect($request);
    }

    public function callback(GoogleCallbackRequest $request)
    {
        $result = $this->google->handleCallback($request);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Google login failed.',
            ], $result['status'] ?? 422);
        }

        $user = $result['user']->load('role');
        $token = $this->auth->issueToken($user);
        $payload = [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->toArray($request),
        ];

        $redirectTo = $result['redirect_to'] ?? null;
        $wantsJson = (bool) ($result['wants_json'] ?? false);

        if ($redirectTo && !$wantsJson) {
            $query = http_build_query([
                'token' => $payload['token'],
                'user' => json_encode($payload['user']),
            ]);

            return redirect()->away($redirectTo . '?' . $query);
        }

        return response()->json([
            'success' => true,
            'message' => 'Google login success.',
            'data' => $payload,
        ]);
    }
}
