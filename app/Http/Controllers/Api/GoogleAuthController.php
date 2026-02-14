<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
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

        return response()->json([
            'success' => true,
            'message' => 'Google login success.',
            'data' => (new AuthResource($token, $user))->toArray(request()),
        ]);
    }
}
