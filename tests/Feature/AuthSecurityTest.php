<?php

namespace Tests\Feature;

use App\Mail\Contact\AdminContactMail;
use App\Mail\Contact\UserContactConfirmationMail;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;
use App\Services\GoogleAuthService;
use App\Services\MicrosoftAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRole = Role::where('slug', Role::USER_SLUG)->firstOrFail();
    }

    public function test_login_route_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->userRole->id,
            'email' => 'rate-limit@example.test',
            'password' => Hash::make('CorrectPassword123!'),
            'is_verified' => true,
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_contact_route_is_rate_limited_and_does_not_use_hardcoded_admin_fallback(): void
    {
        config(['contact.admin_email' => null]);
        Mail::fake();

        $payload = [
            'name' => 'Rate Limited Sender',
            'email' => 'sender@gmail.com',
            'message' => 'Testing the contact security controls.',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/contact', $payload)->assertOk();
        }

        $this->postJson('/api/contact', $payload)->assertStatus(429);

        Mail::assertQueued(UserContactConfirmationMail::class, 5);
        Mail::assertNotQueued(AdminContactMail::class);
    }

    public function test_google_callback_rejects_missing_state(): void
    {
        $this->getJson('/api/auth/google/callback')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid or expired social login state.');
    }

    public function test_google_callback_redirects_token_in_fragment_not_query(): void
    {
        $user = User::factory()->create(['role_id' => $this->userRole->id]);

        $this->mock(GoogleAuthService::class, function ($mock) use ($user) {
            $mock->shouldReceive('handleCallback')
                ->once()
                ->andReturn([
                    'success' => true,
                    'user' => $user,
                    'redirect_to' => 'https://frontend.test/auth/callback',
                    'wants_json' => false,
                ]);
        });

        $this->mock(AuthService::class, function ($mock) {
            $mock->shouldReceive('issueToken')
                ->once()
                ->andReturn('plain-text-token');
        });

        $response = $this->get('/api/auth/google/callback?state=valid-state');
        $location = $response->headers->get('Location');

        $response->assertRedirect();
        $this->assertNotNull($location);
        $this->assertStringContainsString('https://frontend.test/auth/callback#token=plain-text-token', $location);
        $this->assertStringNotContainsString('?token=', $location);
    }

    public function test_microsoft_callback_does_not_expose_provider_errors(): void
    {
        $this->mock(MicrosoftAuthService::class, function ($mock) {
            $mock->shouldReceive('handleCallback')
                ->once()
                ->andReturn([
                    'success' => false,
                    'status' => 422,
                    'message' => 'Microsoft login failed.',
                    'error' => 'sensitive provider response',
                ]);
        });

        $this->getJson('/api/auth/microsoft/callback?state=valid-state')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Microsoft login failed.')
            ->assertJsonMissingPath('error');
    }

    public function test_google_oauth_state_cannot_be_replayed(): void
    {
        config([
            'services.frontend_redirect_whitelist' => ['https://frontend.test/auth/callback'],
        ]);

        $service = app(GoogleAuthService::class);
        $capturedState = null;

        $redirectDriver = Mockery::mock();
        $redirectDriver->shouldReceive('stateless')->once()->andReturnSelf();
        $redirectDriver->shouldReceive('with')
            ->once()
            ->with(Mockery::on(function (array $parameters) use (&$capturedState) {
                $capturedState = $parameters['state'] ?? null;

                return isset($parameters['state'], $parameters['prompt']);
            }))
            ->andReturnSelf();
        $redirectDriver->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        $callbackDriver = Mockery::mock();
        $callbackDriver->shouldReceive('stateless')->once()->andReturnSelf();
        $callbackDriver->shouldReceive('user')->once()->andReturn(new class
        {
            public array $user = ['verified_email' => true];

            public function getId(): string
            {
                return 'google-user-123';
            }

            public function getEmail(): string
            {
                return 'social-user@example.test';
            }

            public function getName(): string
            {
                return 'Social User';
            }

            public function getAvatar(): string
            {
                return 'https://frontend.test/avatar.png';
            }
        });

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn($redirectDriver, $callbackDriver);

        $service->redirect(Request::create('/api/auth/google/redirect', 'GET', [
            'redirect_to' => 'https://frontend.test/auth/callback',
        ]));

        $this->assertNotNull($capturedState);

        $firstAttempt = $service->handleCallback(Request::create('/api/auth/google/callback', 'GET', [
            'state' => $capturedState,
        ]));

        $replayAttempt = $service->handleCallback(Request::create('/api/auth/google/callback', 'GET', [
            'state' => $capturedState,
        ]));

        $this->assertTrue($firstAttempt['success']);
        $this->assertFalse($replayAttempt['success']);
        $this->assertSame('Invalid or expired social login state.', $replayAttempt['message']);
        $this->assertDatabaseHas('users', ['email' => 'social-user@example.test']);
    }
}
