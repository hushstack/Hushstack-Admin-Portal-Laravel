<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthService
{
    private const STATE_TTL_SECONDS = 600;

    public function redirect(Request $request)
    {
        $redirectTo = $this->validateRedirectTo($request->query('redirect_to'));

        $state = $this->encodeState([
            'redirect_to' => $redirectTo,
            'ts' => time(),
            'nonce' => Str::random(16),
            'provider' => 'microsoft',
        ]);

        return Socialite::driver('microsoft')
            ->stateless()
            ->with([
                'state' => $state,
                // Force Microsoft to ask account selection each login.
                'prompt' => 'select_account',
            ])
            ->redirect();
    }

    /**
     * @return array{success:bool,status?:int,message?:string,error?:string,user?:User,redirect_to?:string,wants_json?:bool}
     */
    public function handleCallback(Request $request): array
    {
        try {
            $ms = Socialite::driver('microsoft')
                ->stateless()
                ->user();

            $provider = 'microsoft';
            $providerId = (string) $ms->getId();
            $email = $ms->getEmail();
            $name = $ms->getName() ?: ($ms->getNickname() ?: 'Microsoft User');

            $user = User::where('provider', $provider)
                ->where('provider_id', $providerId)
                ->first();

            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                if (!$email) {
                    return [
                        'success' => false,
                        'status' => 422,
                        'message' => 'Microsoft did not return an email. Please allow email permission or use another login method.',
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
                    'password' => Hash::make(Str::random(32)),
                    'is_verified' => true,
                    'email_verified_at' => now(),
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'role_id' => $roleId,
                ]);
            } else {
                $user->update([
                    'provider' => $user->provider ?: $provider,
                    'provider_id' => $user->provider_id ?: $providerId,
                    'is_verified' => true,
                    'email_verified_at' => $user->email_verified_at ?: now(),
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
            Log::warning('Microsoft login failed.', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 422,
                'message' => 'Microsoft login failed.',
                'error' => $e->getMessage(),
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

        if (($data['provider'] ?? null) !== 'microsoft') {
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
        return (string) config('app.key', 'microsoft-oauth-state-fallback-key');
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
