<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_users(): void
    {
        // Setup: Create roles
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $userRole = Role::firstOrCreate(['slug' => 'user'], ['name' => 'User']);

        // Create an admin user
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@example.com'
        ]);

        // Create a target user to search for
        User::factory()->create([
            'role_id' => $userRole->id,
            'first_name' => 'Peter',
            'last_name' => 'Parker',
            'email' => 'peter@example.com'
        ]);

        // Authenticate as admin
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users/search?q=pe');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'status',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                            'role'
                        ]
                    ]
                ]
            ]);
    }

    public function test_non_admin_cannot_search_users(): void
    {
        $userRole = Role::firstOrCreate(['slug' => 'user'], ['name' => 'User']);
        $user = User::factory()->create(['role_id' => $userRole->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/users/search?q=pe');

        $response->assertStatus(403);
    }
}
