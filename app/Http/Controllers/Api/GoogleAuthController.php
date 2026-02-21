<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    private function validateRedirectTo(?string $redirectTo): ?string
    {
        if (!$redirectTo) return null;

        $allowed = config('services.frontend_redirect_whitelist', []);
        $redirectTo = rtrim($redirectTo, '/');

        foreach ($allowed as $a) {
            if ($redirectTo === rtrim($a, '/')) return $redirectTo;
        }
        return null;
    }

    private function encodeState(array $data): string
    {
        return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
    }

    private function decodeState(?string $state): array
    {
        if (!$state) return [];
        $json = base64_decode(strtr($state, '-_', '+/'));
        $arr = json_decode($json ?: '', true);
        return is_array($arr) ? $arr : [];
    }

    private function wantsJson(Request $request): bool
    {
        return $request->expectsJson()
            || str_contains($request->header('Accept', ''), 'application/json')
            || $request->query('json') === '1';
    }

    public function redirect(Request $request)
    {
        $redirectTo = $this->validateRedirectTo($request->query('redirect_to'));

        $state = $this->encodeState([
            'redirect_to' => $redirectTo,
            'ts' => time(),
            'provider' => 'google',
        ]);

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    public function callback(Request $request)
    {
        $g = Socialite::driver('google')->stateless()->user();

        $user = User::where(function ($q) use ($g) {
            $q->where('provider', 'google')->where('provider_id', $g->getId());
        })
            ->orWhere('email', $g->getEmail())
            ->first();

        if (!$user) {
            $user = User::create([
                'username' => Str::slug(($g->getName() ?: 'user') . '-' . Str::random(6)),
                'first_name' => $g->user['given_name'] ?? 'Google',
                'last_name' => $g->user['family_name'] ?? 'User',
                'email' => $g->getEmail(),
                'phone_number' => 'google_' . Str::random(10),
                'password' => Hash::make(Str::random(32)),
                'is_verified' => true,
                'email_verified_at' => now(),
                'provider' => 'google',
                'provider_id' => $g->getId(),
                'picture' => $g->getAvatar(),
            ]);
        } else {
            $user->update([
                'provider' => 'google',
                'provider_id' => $g->getId(),
                'is_verified' => true,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        }

        $token = $this->auth->issueToken($user);
        $data = (new AuthResource($token, $user))->toArray($request);

        // NEW: redirect support
        $state = $this->decodeState($request->query('state'));
        $redirectTo = $this->validateRedirectTo($state['redirect_to'] ?? null);

        if ($redirectTo && !$this->wantsJson($request)) {
            $query = http_build_query([
                'token' => $data['token'] ?? null,
                'user'  => json_encode($data['user'] ?? $user),
            ]);

            return redirect()->away($redirectTo . '?' . $query);
        }

        return response()->json([
            'success' => true,
            'message' => 'Google login success.',
            'data' => $data,
        ]);
    }
}
