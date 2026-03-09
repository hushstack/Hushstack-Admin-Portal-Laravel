<?php

namespace App\Services\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait HandlesSocialAuthState
{
    protected function issueState(?string $redirectTo, string $provider): string
    {
        $payload = [
            'redirect_to' => $redirectTo,
            'ts' => time(),
            'nonce' => Str::random(40),
            'provider' => $provider,
        ];

        Cache::put(
            $this->stateCacheKey($provider, $payload['nonce']),
            true,
            now()->addSeconds($this->stateTtlSeconds())
        );

        return $this->encodeState($payload);
    }

    protected function consumeState(?string $state, string $provider): ?array
    {
        $payload = $this->decodeState($state, $provider);
        $nonce = (string) ($payload['nonce'] ?? '');

        if ($nonce === '') {
            return null;
        }

        $cacheKey = $this->stateCacheKey($provider, $nonce);

        if (!Cache::pull($cacheKey)) {
            return null;
        }

        return $payload;
    }

    protected function validateRedirectTo(?string $redirectTo): ?string
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

    protected function wantsJson(Request $request): bool
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

    private function decodeState(?string $state, string $provider): array
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

        if (($data['provider'] ?? null) !== $provider) {
            return [];
        }

        $ts = isset($data['ts']) ? (int) $data['ts'] : 0;

        if ($ts <= 0 || (time() - $ts) > $this->stateTtlSeconds()) {
            return [];
        }

        return $data;
    }

    private function stateCacheKey(string $provider, string $nonce): string
    {
        return "social_oauth_state:{$provider}:{$nonce}";
    }

    private function stateSigningKey(): string
    {
        return (string) config('app.key', 'social-oauth-state-fallback-key');
    }

    private function stateTtlSeconds(): int
    {
        return 600;
    }
}
