<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthService
{
    private const STATE_TTL_SECONDS = 600;

    public function redirect(Request $request)
    {
        $redirectTo = $this->validateRedirectTo($request->query('redirect_to'));

        $state = $this->encodeState([
            'redirect_to' => $redirectTo,
            'ts' => time(),
            'nonce' => Str::random(16),
            'provider' => 'google',
        ]);

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

            $state = $this->decodeState($request->query('state'));
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

    private function validateRedirectTo(?string $redirectTo): ?string
    {
        if (!$redirectTo) {
            return null;
        }

        $allowed = config('services.frontend_redirect_whitelist', []);
        $redirectTo = rtrim($redirectTo, '/');

        foreach ($allowed as $candidate) {
            if ($redirectTo === rtrim($candidate, '/')) {
                return $redirectTo;
            }
        }

        return null;
    }

    private function wantsJson(Request $request): bool
    {
        return $request->expectsJson()
            || str_contains($request->header('Accept', ''), 'application/json')
            || $request->query('json') === '1';
    }

    private function encodeState(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        $payload = rtrim(strtr(base64_encode($json ?: '{}'), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $payload, $this->stateSigningKey());

        return $payload . '.' . $signature;
    }

    private function decodeState(?string $state): array
    {
        if (!$state || !str_contains($state, '.')) {
            return [];
        }

        [$payload, $signature] = explode('.', $state, 2);
        $expected = hash_hmac('sha256', $payload, $this->stateSigningKey());

        if (!hash_equals($expected, $signature)) {
            return [];
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($decoded === false) {
            return [];
        }

        $data = json_decode($decoded, true);
        if (!is_array($data)) {
            return [];
        }

        if (($data['provider'] ?? null) !== 'google') {
            return [];
        }

        $ts = isset($data['ts']) ? (int) $data['ts'] : 0;
        if ($ts <= 0 || (time() - $ts) > self::STATE_TTL_SECONDS) {
            return [];
        }

        return $data;
    }

    private function stateSigningKey(): string
    {
        return (string) config('app.key', 'google-oauth-state-fallback-key');
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
