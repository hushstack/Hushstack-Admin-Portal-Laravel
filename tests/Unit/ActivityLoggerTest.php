<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\ActivityLogger;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    public function test_activity_is_logged_with_user_id_and_role_id(): void
    {
        // Setup: Create a role and a user
        $role = Role::firstOrCreate(['id' => 1], [
            'name' => 'User',
            'slug' => 'user',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'picture' => 'profile.jpg',
        ]);

        $logger = new ActivityLogger;
        $logger->log($user, 'Test activity', '127.0.0.1');

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'role_id' => $user->role_id,
            'activity' => 'Test activity',
            'ip_address' => '127.0.0.1',
        ]);

        $activity = UserActivity::first();
        $this->assertEquals('John', $activity->user->first_name);
        $this->assertEquals('User', $activity->role->name);
    }

    public function test_activity_is_logged_without_user(): void
    {
        $logger = new ActivityLogger;
        $logger->log(null, 'Guest activity', '127.0.0.1');

        $this->assertDatabaseHas('user_activities', [
            'user_id' => null,
            'role_id' => null,
            'activity' => 'Guest activity',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
