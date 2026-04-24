<?php

namespace Tests\Feature;

use App\Models\CliLoginRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\CliAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class CliAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('sanctum.cli_auth.device_code_ttl', 600);
        Config::set('sanctum.cli_auth.poll_interval', 5);
        Config::set('sanctum.cli_auth.access_token_ttl', 15);
    }

    public function test_start_login_success(): void
    {
        $response = $this->postJson('/api/cli/auth/start', [
            'client_name' => 'hushstack-cli',
            'client_version' => '1.0.0',
            'device_name' => 'test-box',
            'requested_abilities' => ['profile:read'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.interval', 5);

        $this->assertDatabaseCount('cli_login_requests', 1);
    }

    public function test_start_login_validation_failure(): void
    {
        $response = $this->postJson('/api/cli/auth/start', [
            'requested_abilities' => ['Not Allowed'],
        ]);

        $response->assertStatus(422);
    }

    public function test_exchange_while_pending(): void
    {
        $start = $this->postJson('/api/cli/auth/start', [
            'client_name' => 'hushstack-cli',
        ]);

        $deviceCode = $start->json('data.device_code');

        $response = $this->postJson('/api/cli/auth/exchange', [
            'device_code' => $deviceCode,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'authorization_pending');
    }

    public function test_exchange_after_approval(): void
    {
        $userRole = Role::where('slug', Role::USER_SLUG)->firstOrFail();
        $user = User::factory()->create(['role_id' => $userRole->id]);

        $start = app(CliAuthService::class)->start([
            'client_name' => 'hushstack-cli',
            'device_name' => 'dev-machine',
        ], request()->create('/api/cli/auth/start', 'POST'));

        app(CliAuthService::class)->approve($start['login_request'], $user);

        $response = $this->postJson('/api/cli/auth/exchange', [
            'device_code' => $start['device_code'],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email);

        $this->assertDatabaseHas('cli_login_requests', [
            'id' => $start['login_request']->id,
            'status' => CliLoginRequest::STATUS_CONSUMED,
        ]);

        $this->assertTrue(PersonalAccessToken::query()->where('name', 'like', 'cli:%')->exists());
    }

    public function test_exchange_with_invalid_code(): void
    {
        $response = $this->postJson('/api/cli/auth/exchange', [
            'device_code' => 'invalid',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error', 'invalid_device_code');
    }

    public function test_exchange_after_expiration(): void
    {
        $start = app(CliAuthService::class)->start([
            'client_name' => 'hushstack-cli',
        ], request()->create('/api/cli/auth/start', 'POST'));

        $start['login_request']->forceFill([
            'expires_at' => now()->subMinute(),
        ])->save();

        $response = $this->postJson('/api/cli/auth/exchange', [
            'device_code' => $start['device_code'],
        ]);

        $response->assertStatus(410)
            ->assertJsonPath('error', 'expired_token');
    }

    public function test_exchange_after_already_consumed(): void
    {
        $userRole = Role::where('slug', Role::USER_SLUG)->firstOrFail();
        $user = User::factory()->create(['role_id' => $userRole->id]);

        $start = app(CliAuthService::class)->start([
            'client_name' => 'hushstack-cli',
        ], request()->create('/api/cli/auth/start', 'POST'));

        app(CliAuthService::class)->approve($start['login_request'], $user);

        $this->postJson('/api/cli/auth/exchange', ['device_code' => $start['device_code']])->assertOk();

        $response = $this->postJson('/api/cli/auth/exchange', [
            'device_code' => $start['device_code'],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('error', 'already_consumed');
    }

    public function test_logout_success(): void
    {
        $userRole = Role::where('slug', Role::USER_SLUG)->firstOrFail();
        $user = User::factory()->create(['role_id' => $userRole->id]);
        $token = $user->createToken('cli:test', ['cli'], now()->addMinutes(15))->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/cli/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_and_logout_require_authentication(): void
    {
        $this->getJson('/api/cli/auth/me')->assertStatus(401);
        $this->postJson('/api/cli/auth/logout')->assertStatus(401);
    }

    public function test_browser_verification_approval_path(): void
    {
        $userRole = Role::where('slug', Role::USER_SLUG)->firstOrFail();
        $user = User::factory()->create(['role_id' => $userRole->id]);

        $start = app(CliAuthService::class)->start([
            'client_name' => 'hushstack-cli',
            'device_name' => 'browser-mac',
        ], request()->create('/api/cli/auth/start', 'POST'));

        $response = $this->actingAs($user)
            ->post(route('cli-auth.request.approve', $start['login_request']));

        $response->assertOk()->assertSeeText('CLI login approved successfully');

        $this->assertDatabaseHas('cli_login_requests', [
            'id' => $start['login_request']->id,
            'status' => CliLoginRequest::STATUS_APPROVED,
            'user_id' => $user->id,
        ]);
    }

    public function test_start_login_rate_limiting(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/cli/auth/start', ['client_name' => 'hushstack-cli'])
                ->assertCreated();
        }

        $this->postJson('/api/cli/auth/start', ['client_name' => 'hushstack-cli'])
            ->assertStatus(429);
    }
}
