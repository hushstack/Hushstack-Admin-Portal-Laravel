<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function defaultAccessTokenTtl(): \DateTimeInterface
    {
        return now()->addMinutes((int) config('sanctum.default_token_expiration', 60 * 24 * 7));
    }

    public function cliAccessTokenTtl(): \DateTimeInterface
    {
        return now()->addMinutes((int) config('sanctum.cli_auth.access_token_ttl', 15));
    }

    public function register(array $data): User
    {
        // Manual registration always gets the default role (ignore client input).
        $roleId = Role::defaultId();
        if ($roleId <= 0) {
            throw new \RuntimeException('Default role not configured.');
        }

        return User::create([
            'username' => $data['username'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'birth_of_date' => $data['birth_of_date'],
            'password' => Hash::make($data['password']),
            'is_verified' => false,
            'role_id' => $roleId,
        ]);
    }

    public function validateCredentials(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return null;
        }

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function issueToken(User $user): string
    {
        return $user->createToken('api', ['*'], $this->defaultAccessTokenTtl())->plainTextToken;
    }

    public function issueCliToken(User $user, string $tokenName, array $abilities = ['cli']): array
    {
        $token = $user->createToken(
            $tokenName,
            $abilities === [] ? ['cli'] : $abilities,
            $this->cliAccessTokenTtl()
        );

        return [
            'plain_text_token' => $token->plainTextToken,
            'access_token' => $token->accessToken,
        ];
    }

    public function makeCliTokenName(string $deviceName): string
    {
        $normalized = preg_replace('/[^a-z0-9]+/i', '-', trim($deviceName)) ?: 'device';
        $normalized = trim((string) $normalized, '-');

        return 'cli:'.strtolower($normalized).':'.now()->utc()->format('YmdHis');
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return true;
    }
}
