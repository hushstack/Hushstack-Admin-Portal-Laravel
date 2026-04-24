<?php

namespace App\Services;

use App\Models\CliLoginRequest;
use App\Models\Role;
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
            'mode' => 'default',
        ]);

        return $this->defaultGoogleDriver()
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
            $googleUser = $this->defaultGoogleDriver()->user();
            $user = $this->resolveGoogleUser($googleUser);
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

    public function redirectForCli(CliLoginRequest $loginRequest)
    {
        $state = $this->encodeState([
            'ts' => time(),
            'nonce' => Str::random(16),
            'provider' => 'google',
            'mode' => 'cli',
            'cli_login_request_id' => $loginRequest->id,
        ]);

        return $this->cliGoogleDriver()
            ->with([
                'state' => $state,
                'prompt' => 'select_account consent',
            ])
            ->redirect();
    }

    public function handleCliCallback(Request $request): array
    {
        try {
            $googleUser = $this->cliGoogleDriver()->user();
            $user = $this->resolveGoogleUser($googleUser);
            $state = $this->decodeState($request->query('state'));
            $loginRequestId = (int) ($state['cli_login_request_id'] ?? 0);

            if (($state['mode'] ?? null) !== 'cli' || $loginRequestId <= 0) {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => 'CLI login request is invalid or expired.',
                ];
            }

            $loginRequest = CliLoginRequest::query()->find($loginRequestId);
            if (! $loginRequest) {
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => 'CLI login request not found.',
                ];
            }

            return [
                'success' => true,
                'user' => $user,
                'login_request' => $loginRequest,
            ];
        } catch (\Throwable $e) {
            Log::warning('Google CLI login failed.', [
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
        if (! $redirectTo) {
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

        return $payload.'.'.$signature;
    }

    private function decodeState(?string $state): array
    {
        if (! $state || ! str_contains($state, '.')) {
            return [];
        }

        [$payload, $signature] = explode('.', $state, 2);
        $expected = hash_hmac('sha256', $payload, $this->stateSigningKey());

        if (! hash_equals($expected, $signature)) {
            return [];
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($decoded === false) {
            return [];
        }

        $data = json_decode($decoded, true);
        if (! is_array($data)) {
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

    private function resolveGoogleUser($googleUser): User
    {
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

        if (! $user && $email && $emailVerified) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            if (! $email || ! $emailVerified) {
                throw new \RuntimeException('Google account must provide a verified email address.');
            }

            [$first, $last] = $this->splitName($name);

            $roleId = Role::idBySlug(Role::USER_SLUG);
            if ($roleId <= 0) {
                throw new \RuntimeException('Default role not configured.');
            }

            return User::create([
                'first_name' => $first,
                'last_name' => $last,
                'username' => $this->makeUsername($email),
                'email' => $email,
                'social_login_key' => 'google_'.Str::lower(Str::random(10)),
                'password' => Hash::make(Str::random(32)),
                'is_verified' => true,
                'email_verified_at' => now(),
                'provider' => $provider,
                'provider_id' => $providerId,
                'picture' => $avatar,
                'role_id' => $roleId,
            ]);
        }

        $user->update([
            'provider' => $user->provider ?: $provider,
            'provider_id' => $user->provider_id ?: $providerId,
            'is_verified' => true,
            'email_verified_at' => $user->email_verified_at ?: now(),
            'picture' => $user->picture ?: $avatar,
        ]);

        return $user;
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

        return $base.'_'.Str::lower(Str::random(4));
    }

    private function defaultGoogleDriver()
    {
        return Socialite::driver('google')
            ->stateless();
    }

    private function cliGoogleDriver()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirectUrl(route('cli-auth.google.callback'));
    }
}
