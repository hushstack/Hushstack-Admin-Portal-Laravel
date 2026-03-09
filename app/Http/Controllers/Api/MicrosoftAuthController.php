<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MicrosoftCallbackRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\MicrosoftAuthService;
use Illuminate\Http\Request;

class MicrosoftAuthController extends Controller
{
    public function __construct(
        private MicrosoftAuthService $microsoft,
        private AuthService $auth
    ) {}

    public function redirect(Request $request)
    {
        return $this->microsoft->redirect($request);
    }

    public function callback(MicrosoftCallbackRequest $request)
    {
        $result = $this->microsoft->handleCallback($request);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Microsoft login failed.',
            ], $result['status'] ?? 422);
        }

        $user = $result['user']->load('role');
        $token = $this->auth->issueToken($user);

        $payload = [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->toArray(request()),
        ];

        $redirectTo = $result['redirect_to'] ?? null;
        $wantsJson = (bool) ($result['wants_json'] ?? false);

        if ($redirectTo && !$wantsJson) {
            $fragment = http_build_query([
                'token' => $payload['token'],
                'user'  => json_encode($payload['user']),
            ]);

            return redirect()->away($redirectTo . '#' . $fragment);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login with Microsoft success.',
            'data' => $payload,
        ]);
    }
}
