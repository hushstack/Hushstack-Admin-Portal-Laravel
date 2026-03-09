<?php

namespace App\Services;

use App\Services\Concerns\HandlesSocialAuthState;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService
{
    use HandlesSocialAuthState;

    public function redirect(Request $request)
    {
        $redirectTo = $this->validateRedirectTo($request->query('redirect_to'));

        $state = $this->issueState($redirectTo, 'google');

        return Socialite::driver('google')
            ->stateless()
            ->with([
                'state' => $state,
                // Force Google to always show account picker + consent screen.
                'prompt' => 'select_account consent',
            ])
            ->redirect();
    }

    /**
     * @return array{success:bool,status?:int,message?:string,user?:User,redirect_to?:string,wants_json?:bool}
     */
    public function handleCallback(Request $request): array
    {
        $state = $this->consumeState($request->query('state'), 'google');

        if (!$state) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Invalid or expired social login state.',
            ];
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $provider = 'google';
            $providerId = (string) $googleUser->getId();
            $email = $googleUser->getEmail();
            $name = $googleUser->getName() ?: 'Google User';
            $avatar = $googleUser->getAvatar();
            $raw = is_array($googleUser->user ?? null) ? $googleUser->user : [];
            $emailVerified = (bool) ($raw['verified_email'] ?? false);

            $user = User::where('provider', $provider)
                ->where('provider_id', $providerId)
                ->first();

            if (!$user && $email && $emailVerified) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                if (!$email || !$emailVerified) {
                    return [
                        'success' => false,
                        'status' => 422,
                        'message' => 'Google account must provide a verified email address.',
                    ];
                }

                [$first, $last] = $this->splitName($name);

                $roleId = Role::idBySlug(Role::USER_SLUG);
                if ($roleId <= 0) {
                    throw new \RuntimeException('Default role not configured.');
                }

                $user = User::create([
                    'first_name' => $first,
                    'last_name' => $last,
                    'username' => $this->makeUsername($email),
                    'email' => $email,
                    'social_login_key' => 'google_' . Str::lower(Str::random(10)),
                    'password' => Hash::make(Str::random(32)),
                    'is_verified' => true,
                    'email_verified_at' => now(),
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'picture' => $avatar,
                    'role_id' => $roleId,
                ]);
            } else {
                $user->update([
                    'provider' => $user->provider ?: $provider,
                    'provider_id' => $user->provider_id ?: $providerId,
                    'is_verified' => true,
                    'email_verified_at' => $user->email_verified_at ?: now(),
                    'picture' => $user->picture ?: $avatar,
                ]);
            }

            $redirectTo = $this->validateRedirectTo($state['redirect_to'] ?? null);

            return [
                'success' => true,
                'user' => $user,
                'redirect_to' => $redirectTo,
                'wants_json' => $this->wantsJson($request),
            ];
        } catch (\Throwable $e) {
            Log::warning('Google login failed.', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 422,
                'message' => 'Google login failed.',
            ];
        }
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        $first = $parts[0] ?? 'Google';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'User';

        return [$first, $last];
    }

    private function makeUsername(string $email): string
    {
        $base = Str::before($email, '@');

        return $base . '_' . Str::lower(Str::random(4));
    }
}
