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

class MicrosoftAuthService
{
    use HandlesSocialAuthState;

    public function redirect(Request $request)
    {
        $redirectTo = $this->validateRedirectTo($request->query('redirect_to'));

        $state = $this->issueState($redirectTo, 'microsoft');

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
     * @return array{success:bool,status?:int,message?:string,user?:User,redirect_to?:string,wants_json?:bool}
     */
    public function handleCallback(Request $request): array
    {
        $state = $this->consumeState($request->query('state'), 'microsoft');

        if (!$state) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Invalid or expired social login state.',
            ];
        }

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
