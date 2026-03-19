<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;

class ActivityLogger
{
    public function log(?User $user, string $activity, ?string $ip = null): void
    {
        UserActivity::create([
            'user_id' => $user?->id,
            'role_id' => $user?->role_id,
            'activity' => $activity,
            'ip_address' => $ip,
        ]);
    }
}
