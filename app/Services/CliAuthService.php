<?php

namespace App\Services;

use App\Models\CliLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CliAuthService
{
    public function __construct(private AuthService $authService) {}

    public function start(array $payload, Request $request): array
    {
        $deviceCode = Str::random(96);
        $userCode = $this->generateUserCode();
        $expiresAt = now()->addSeconds($this->deviceCodeTtlSeconds());

        $loginRequest = CliLoginRequest::create([
            'device_code_hash' => $this->hashCode($deviceCode),
            'user_code_hash' => $this->hashCode($this->normalizeUserCode($userCode)),
            'status' => CliLoginRequest::STATUS_PENDING,
            'requested_abilities' => $this->normalizeAbilities($payload['requested_abilities'] ?? []),
            'client_name' => $payload['client_name'] ?? null,
            'client_version' => $payload['client_version'] ?? null,
            'device_name' => $payload['device_name'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'expires_at' => $expiresAt,
        ]);

        $verificationUri = route('cli-auth.verify');

        return [
            'login_request' => $loginRequest,
            'device_code' => $deviceCode,
            'user_code' => $userCode,
            'verification_uri' => $verificationUri,
            'verification_uri_complete' => route('cli-auth.authorize', ['deviceCode' => $deviceCode]),
            'interval' => $this->pollIntervalSeconds(),
            'expires_in' => now()->diffInSeconds($expiresAt),
        ];
    }

    public function findByDeviceCode(string $deviceCode): ?CliLoginRequest
    {
        $loginRequest = CliLoginRequest::query()
            ->where('device_code_hash', $this->hashCode($deviceCode))
            ->first();

        return $this->expireIfNeeded($loginRequest);
    }

    public function findByUserCode(string $userCode): ?CliLoginRequest
    {
        $loginRequest = CliLoginRequest::query()
            ->where('user_code_hash', $this->hashCode($this->normalizeUserCode($userCode)))
            ->first();

        return $this->expireIfNeeded($loginRequest);
    }

    public function approve(CliLoginRequest $loginRequest, User $user): CliLoginRequest
    {
        $loginRequest = $this->expireIfNeeded($loginRequest);

        if (! $loginRequest || in_array($loginRequest->status, [
            CliLoginRequest::STATUS_CONSUMED,
            CliLoginRequest::STATUS_EXPIRED,
            CliLoginRequest::STATUS_DENIED,
        ], true)) {
            return $loginRequest;
        }

        if ($loginRequest->status !== CliLoginRequest::STATUS_APPROVED) {
            $loginRequest->forceFill([
                'status' => CliLoginRequest::STATUS_APPROVED,
                'user_id' => $user->id,
                'approved_at' => now(),
            ])->save();
        }

        return $loginRequest->fresh(['user']);
    }

    public function exchange(string $deviceCode): array
    {
        $loginRequest = $this->findByDeviceCode($deviceCode);

        if (! $loginRequest) {
            return $this->errorResult('invalid_device_code', 'Device code is invalid.', 401);
        }

        if ($loginRequest->status === CliLoginRequest::STATUS_EXPIRED) {
            return $this->errorResult('expired_token', 'Device code has expired.', 410);
        }

        if ($loginRequest->status === CliLoginRequest::STATUS_CONSUMED) {
            return $this->errorResult('already_consumed', 'Device code has already been exchanged.', 409);
        }

        if ($loginRequest->status === CliLoginRequest::STATUS_DENIED) {
            return $this->errorResult('access_denied', 'Login request has been denied.', 403);
        }

        if ($loginRequest->status === CliLoginRequest::STATUS_PENDING) {
            if ($this->isPollingTooFast($loginRequest)) {
                $loginRequest->forceFill(['last_polled_at' => now()])->save();

                return $this->errorResult('slow_down', 'Polling too quickly. Please slow down.', 429);
            }

            $loginRequest->forceFill(['last_polled_at' => now()])->save();

            return $this->errorResult('authorization_pending', 'Login not completed in browser yet.', 400);
        }

        return DB::transaction(function () use ($loginRequest) {
            $locked = CliLoginRequest::query()
                ->whereKey($loginRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked = $this->expireIfNeeded($locked);

            if ($locked->status === CliLoginRequest::STATUS_CONSUMED) {
                return $this->errorResult('already_consumed', 'Device code has already been exchanged.', 409);
            }

            if ($locked->status === CliLoginRequest::STATUS_EXPIRED) {
                return $this->errorResult('expired_token', 'Device code has expired.', 410);
            }

            if ($locked->status !== CliLoginRequest::STATUS_APPROVED || ! $locked->user) {
                return $this->errorResult('authorization_pending', 'Login not completed in browser yet.', 400);
            }

            $tokenName = $this->authService->makeCliTokenName(
                $locked->device_name ?: $locked->client_name ?: 'device'
            );
            $token = $this->authService->issueCliToken(
                $locked->user->load('role'),
                $tokenName,
                $locked->requested_abilities ?? []
            );

            $locked->forceFill([
                'status' => CliLoginRequest::STATUS_CONSUMED,
                'consumed_at' => now(),
            ])->save();

            return [
                'ok' => true,
                'status' => 200,
                'data' => [
                    'access_token' => $token['plain_text_token'],
                    'expires_at' => $token['access_token']->expires_at,
                    'user' => $locked->user,
                ],
            ];
        });
    }

    public function normalizeAbilities(array $abilities): array
    {
        $normalized = collect($abilities)
            ->filter(fn ($ability) => is_string($ability) && $ability !== '')
            ->map(fn (string $ability) => Str::lower(trim($ability)))
            ->unique()
            ->values()
            ->all();

        if ($normalized === []) {
            return ['cli'];
        }

        if (! in_array('cli', $normalized, true)) {
            array_unshift($normalized, 'cli');
        }

        return $normalized;
    }

    public function deviceCodeTtlSeconds(): int
    {
        return max(60, (int) config('sanctum.cli_auth.device_code_ttl', 600));
    }

    public function pollIntervalSeconds(): int
    {
        return max(1, (int) config('sanctum.cli_auth.poll_interval', 5));
    }

    private function expireIfNeeded(?CliLoginRequest $loginRequest): ?CliLoginRequest
    {
        if (! $loginRequest) {
            return null;
        }

        if ($loginRequest->status !== CliLoginRequest::STATUS_EXPIRED && $loginRequest->isExpired()) {
            $loginRequest->forceFill(['status' => CliLoginRequest::STATUS_EXPIRED])->save();
        }

        return $loginRequest;
    }

    private function hashCode(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function normalizeUserCode(string $code): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }

    private function generateUserCode(): string
    {
        $segments = [
            Str::upper(Str::random(4)),
            Str::upper(Str::random(4)),
        ];

        return implode('-', $segments);
    }

    private function isPollingTooFast(CliLoginRequest $loginRequest): bool
    {
        if (! $loginRequest->last_polled_at instanceof Carbon) {
            return false;
        }

        return now()->diffInRealSeconds($loginRequest->last_polled_at) < $this->pollIntervalSeconds();
    }

    private function errorResult(string $error, string $message, int $status): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'error' => $error,
            'message' => $message,
        ];
    }
}
