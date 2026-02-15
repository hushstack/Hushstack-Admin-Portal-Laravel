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
        return $this->microsoft->redirect();
    }

    public function callback(MicrosoftCallbackRequest $request)
    {
        $result = $this->microsoft->handleCallback();

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Microsoft login failed.',
                'error' => $result['error'] ?? null,
            ], $result['status'] ?? 422);
        }

        $user = $result['user'];

        $token = $this->auth->issueToken($user);

        return response()->json([
            'success' => true,
            'message' => 'Login with Microsoft success.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->toArray(request()),
        ]);
    }
}
