<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessengerUserApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.messenger.url' => 'https://messenger.test',
            'services.messenger.internal_key' => 'internal-key',
        ]);
    }

    private function admin(): User
    {
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);

        return User::factory()->create(['role_id' => $adminRole->id]);
    }

    public function test_admin_receives_the_messenger_payload_unchanged(): void
    {
        $payload = [
            'data' => [['id' => 1, 'name' => 'Ada']],
            'meta' => ['current_page' => 2, 'per_page' => 15, 'total' => 30],
        ];

        Http::fake(['messenger.test/*' => Http::response($payload, 200)]);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/messenger/users?page=2&per_page=15');

        $response->assertStatus(200)->assertExactJson($payload);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Internal-Key', 'internal-key')
                && str_contains($request->url(), 'https://messenger.test/api/internal/users')
                && str_contains($request->url(), 'page=2')
                && str_contains($request->url(), 'per_page=15');
        });
    }

    public function test_connection_failure_returns_503(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/messenger/users')
            ->assertStatus(503)
            ->assertJsonPath('error_code', 'MESSENGER_API_ERROR');
    }

    public function test_upstream_server_error_returns_503(): void
    {
        Http::fake(['messenger.test/*' => Http::response('boom', 500)]);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/messenger/users')
            ->assertStatus(503);
    }

    public function test_rejected_internal_key_is_not_leaked_downstream(): void
    {
        Http::fake(['messenger.test/*' => Http::response(['message' => 'invalid key'], 401)]);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/messenger/users')
            ->assertStatus(503)
            ->assertJsonMissing(['message' => 'invalid key']);
    }

    public function test_missing_configuration_returns_503(): void
    {
        config(['services.messenger.internal_key' => null]);
        Http::fake();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/messenger/users')
            ->assertStatus(503);

        Http::assertNothingSent();
    }

    public function test_guest_cannot_access_the_endpoint(): void
    {
        $this->getJson('/api/admin/messenger/users')->assertStatus(401);
    }

    public function test_non_admin_cannot_access_the_endpoint(): void
    {
        $userRole = Role::firstOrCreate(['slug' => 'user'], ['name' => 'User']);
        $user = User::factory()->create(['role_id' => $userRole->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/messenger/users')
            ->assertStatus(403);
    }
}
