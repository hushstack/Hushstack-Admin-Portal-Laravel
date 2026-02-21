<?php

namespace  App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthService
{
    public function redirect()
    {
        $redirectTo = request()->query('redirect_to');

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

        $state = rtrim(strtr(base64_encode(json_encode([
            'redirect_to' => $redirectTo,
            'ts' => time(),
            'provider' => 'microsoft',
        ])), '+/', '-_'), '=');

        return Socialite::driver('microsoft')
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * @return array{success:bool,status?:int,message?:string,error?:string,user?:User}
     */
    public function handleCallback(): array
    {
        try {
            $ms = Socialite::driver('microsoft')
                ->stateless()
                ->user();

            $provider = 'microsoft';
            $providerId = (string) $ms->getId();
            $email = $ms->getEmail(); // can be null for some accounts
            $name = $ms->getName() ?: ($ms->getNickname() ?: 'Microsoft User');

            // 1) Best match: provider + provider_id
            $user = User::where('provider', $provider)
                ->where('provider_id', $providerId)
                ->first();

            // 2) If no match, link by email (optional but common)
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            // 3) Create user if not exists
            if (!$user) {
                if (!$email) {
                    return [
                        'success' => false,
                        'status' => 422,
                        'message' => 'Microsoft did not return an email. Please allow email permission or use another login method.',
                    ];
                }

                [$first, $last] = $this->splitName($name);

                $user = User::create([
                    'first_name' => $first,
                    'last_name' => $last,
                    'username' => $this->makeUsername($email),
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)), // random (social login)
                    'is_verified' => true,
                    'email_verified_at' => now(),
                    'provider' => $provider,
                    'provider_id' => $providerId,
                ]);
            } else {
                // ensure linked + verified
                $user->update([
                    'provider' => $user->provider ?: $provider,
                    'provider_id' => $user->provider_id ?: $providerId,
                    'is_verified' => true,
                    'email_verified_at' => $user->email_verified_at ?: now(),
                ]);
            }

            return [
                'success' => true,
                'user' => $user,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Microsoft login failed.',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        $first = $parts[0] ?? 'Microsoft';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'User';

        return [$first, $last];
    }

    private function makeUsername(string $email): string
    {
        $base = Str::before($email, '@');
        return $base . '_' . Str::lower(Str::random(4));
    }
}
