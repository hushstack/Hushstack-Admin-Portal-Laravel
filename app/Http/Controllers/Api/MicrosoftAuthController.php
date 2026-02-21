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

        $payload = [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->toArray(request()),
        ];

        // ---- Redirect support (NEW)
        $state = $request->query('state');
        $json = base64_decode(strtr($state ?? '', '-_', '+/'));
        $arr = json_decode($json ?: '', true) ?: [];
        $redirectTo = $arr['redirect_to'] ?? null;

        // validate exact match against whitelist
        $allowed = config('services.frontend_redirect_whitelist', []);
        $redirectTo = $redirectTo ? rtrim($redirectTo, '/') : null;

        $ok = false;
        if ($redirectTo) {
            foreach ($allowed as $a) {
                if ($redirectTo === rtrim($a, '/')) { $ok = true; break; }
            }
        }
        if (!$ok) $redirectTo = null;

        if ($redirectTo && !$request->expectsJson()) {
            $query = http_build_query([
                'token' => $payload['token'],
                'user'  => json_encode($payload['user']),
            ]);

            return redirect()->away($redirectTo . '?' . $query);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login with Microsoft success.',
            'data' => $payload,
        ]);
    }
}
