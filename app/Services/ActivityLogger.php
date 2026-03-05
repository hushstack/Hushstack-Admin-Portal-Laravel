<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;

class ActivityLogger
{
    public function log(?User $user, string $activity, ?string $ip = null): void
    {
        if ($user && !$user->relationLoaded('role')) {
            $user->load('role');
        }

        UserActivity::create([
            'first_name' => $user?->first_name,
            'last_name' => $user?->last_name,
            'username' => $user?->username,
            'email' => $user?->email,
            'role_name' => $user?->role?->name,
            'activity' => $activity,
            'ip_address' => $ip,
        ]);
    }
}
