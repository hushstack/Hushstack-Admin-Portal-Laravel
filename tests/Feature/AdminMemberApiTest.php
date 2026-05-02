<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminMemberApiTest extends TestCase
{
    protected Role $adminRole;
    protected Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::where('slug', Role::ADMIN_SLUG)->firstOrFail();
        $this->userRole = Role::where('slug', Role::USER_SLUG)->firstOrFail();
    }

    public function test_admin_can_create_position(): void
    {
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        Sanctum::actingAs($admin);

        $payload = [
            'name' => 'Software Engineer',
            'description' => 'Coding experts',
        ];

        $response = $this->postJson('/api/admin/positions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Software Engineer')
            ->assertJsonPath('data.slug', 'software-engineer');

        $this->assertDatabaseHas('positions', ['name' => 'Software Engineer']);
    }

    public function test_admin_can_create_member(): void
    {
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        Sanctum::actingAs($admin);

        $position = Position::create(['name' => 'Software Engineer', 'slug' => 'se']);
        $user = User::factory()->create();

        $payload = [
            'user_id' => $user->id,
            'position_id' => $position->id,
            'long_description' => 'A great developer.',
            'skills' => ['PHP', 'Laravel', 'React'],
            'is_published' => true,
        ];

        $response = $this->postJson('/api/admin/members', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.long_description', 'A great developer.')
            ->assertJsonPath('data.skills', ['PHP', 'Laravel', 'React']);

        $this->assertDatabaseHas('members', ['long_description' => 'A great developer.']);
    }

    public function test_non_admin_cannot_create_position(): void
    {
        $user = User::factory()->create(['role_id' => $this->userRole->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/admin/positions', [
            'name' => 'Software Engineer',
        ]);

        $response->assertStatus(403);
    }
}
