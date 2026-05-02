<?php

namespace Tests\Feature;

use App\Enums\Permission as PermissionEnum;
use App\Models\Alert;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlertApiTest extends TestCase
{
    private string $agentToken = 'cachewraith-test-token';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.cachewraith.agent_token', $this->agentToken);
    }

    public function test_agent_can_create_alert_with_valid_token(): void
    {
        $response = $this->withToken($this->agentToken)
            ->postJson('/api/alerts', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('data.server_name', 'vultr')
            ->assertJsonPath('data.status', Alert::STATUS_OPEN)
            ->assertJsonPath('data.occurrence_count', 1);

        $this->assertDatabaseHas('alerts', [
            'source' => Alert::SOURCE_CACHEWRAITH,
            'server_name' => 'vultr',
            'type' => 'ssh_bruteforce',
            'severity' => Alert::SEVERITY_CRITICAL,
            'fingerprint' => 'ssh_bruteforce:vultr:1.2.3.4',
            'occurrence_count' => 1,
        ]);
    }

    public function test_invalid_agent_token_gets_401(): void
    {
        $response = $this->withToken('wrong-token')
            ->postJson('/api/alerts', $this->payload());

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseCount('alerts', 0);
    }

    public function test_validation_rejects_bad_payload(): void
    {
        $response = $this->withToken($this->agentToken)
            ->postJson('/api/alerts', [
                'server_name' => '',
                'type' => '',
                'severity' => 'panic',
                'title' => '',
                'source_ip' => 'not-an-ip',
                'website_url' => 'not-a-url',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'server_name',
                'type',
                'severity',
                'title',
                'source_ip',
                'website_url',
            ]);

        $this->assertDatabaseCount('alerts', 0);
    }

    public function test_duplicate_fingerprint_increments_occurrence_count(): void
    {
        $this->withToken($this->agentToken)
            ->postJson('/api/alerts', $this->payload())
            ->assertCreated();

        $response = $this->withToken($this->agentToken)
            ->postJson('/api/alerts', array_merge($this->payload(), [
                'message' => 'Latest failed SSH attempts exceeded threshold',
                'occurred_at' => '2026-04-26T22:00:00+07:00',
                'metadata' => ['failed_attempts' => 20],
            ]));

        $response->assertOk()
            ->assertJsonPath('message', 'Alert occurrence updated.')
            ->assertJsonPath('data.occurrence_count', 2)
            ->assertJsonPath('data.message', 'Latest failed SSH attempts exceeded threshold')
            ->assertJsonPath('data.metadata.failed_attempts', 20);

        $this->assertDatabaseCount('alerts', 1);
        $this->assertDatabaseHas('alerts', [
            'fingerprint' => 'ssh_bruteforce:vultr:1.2.3.4',
            'occurrence_count' => 2,
        ]);
    }

    public function test_super_admin_can_list_and_view_alerts(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $alert = Alert::factory()->create([
            'server_name' => 'vultr',
            'severity' => Alert::SEVERITY_CRITICAL,
        ]);

        $this->getJson('/api/admin/alerts')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('data.data.0.id', $alert->id);

        $this->getJson('/api/admin/alerts/'.$alert->id)
            ->assertOk()
            ->assertJsonPath('data.id', $alert->id)
            ->assertJsonPath('data.server_name', 'vultr');
    }

    public function test_normal_user_cannot_access_admin_alert_endpoints(): void
    {
        $userRole = Role::where('slug', Role::USER_SLUG)->firstOrFail();
        $user = User::factory()->create(['role_id' => $userRole->id]);
        $alert = Alert::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/alerts')->assertStatus(403);
        $this->getJson('/api/admin/alerts/'.$alert->id)->assertStatus(403);
    }

    public function test_super_admin_can_acknowledge_resolve_and_reopen_alert(): void
    {
        $admin = $this->superAdmin();
        Sanctum::actingAs($admin);

        $alert = Alert::factory()->create();

        $this->postJson('/api/admin/alerts/'.$alert->id.'/acknowledge')
            ->assertOk()
            ->assertJsonPath('data.status', Alert::STATUS_ACKNOWLEDGED)
            ->assertJsonPath('data.acknowledged_by.id', $admin->id);

        $this->postJson('/api/admin/alerts/'.$alert->id.'/resolve')
            ->assertOk()
            ->assertJsonPath('data.status', Alert::STATUS_RESOLVED)
            ->assertJsonPath('data.resolved_by.id', $admin->id);

        $this->postJson('/api/admin/alerts/'.$alert->id.'/reopen')
            ->assertOk()
            ->assertJsonPath('data.status', Alert::STATUS_OPEN)
            ->assertJsonPath('data.acknowledged_by', null)
            ->assertJsonPath('data.resolved_by', null);
    }

    public function test_filters_work_for_list_endpoint(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $match = Alert::factory()->create([
            'server_name' => 'vultr',
            'type' => 'ssh_bruteforce',
            'severity' => Alert::SEVERITY_CRITICAL,
            'status' => Alert::STATUS_OPEN,
            'source_ip' => '1.2.3.4',
            'title' => 'SSH BRUTE FORCE DETECTED',
            'last_seen_at' => '2026-04-26 15:00:00',
        ]);

        Alert::factory()->create([
            'server_name' => 'other',
            'type' => 'service_down',
            'severity' => Alert::SEVERITY_WARNING,
            'status' => Alert::STATUS_RESOLVED,
            'source_ip' => '5.6.7.8',
            'title' => 'Different alert',
            'last_seen_at' => '2026-04-20 15:00:00',
        ]);

        $this->getJson('/api/admin/alerts?severity=critical&status=open&type=ssh_bruteforce&server_name=vultr&source_ip=1.2.3.4&date_from=2026-04-26&date_to=2026-04-26&search=BRUTE&per_page=10')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $match->id);
    }

    public function test_alert_permissions_are_synced_to_database(): void
    {
        foreach ([
            PermissionEnum::ALERTS_VIEW,
            PermissionEnum::ALERTS_ACKNOWLEDGE,
            PermissionEnum::ALERTS_RESOLVE,
            PermissionEnum::ALERTS_REOPEN,
            PermissionEnum::ALERTS_DELETE,
        ] as $permission) {
            $this->assertTrue(
                Permission::where('slug', $permission->value)->exists(),
                "Missing synced permission [{$permission->value}]."
            );
        }
    }

    private function payload(): array
    {
        return [
            'server_name' => 'vultr',
            'device_name' => 'vultr',
            'type' => 'ssh_bruteforce',
            'severity' => 'critical',
            'title' => 'SSH BRUTE FORCE DETECTED',
            'message' => 'Failed SSH attempts exceeded threshold',
            'source_ip' => '1.2.3.4',
            'user_name' => 'root',
            'auth_method' => 'password',
            'website_url' => null,
            'service_name' => null,
            'path' => null,
            'fingerprint' => 'ssh_bruteforce:vultr:1.2.3.4',
            'metadata' => [
                'failed_attempts' => 10,
                'window' => '5 minutes',
            ],
            'occurred_at' => '2026-04-26T21:40:00+07:00',
        ];
    }

    private function superAdmin(): User
    {
        $role = Role::where('slug', Role::SUPER_ADMIN_SLUG)->first();

        if (! $role) {
            $role = new Role([
                'name' => 'Super Admin',
                'slug' => Role::SUPER_ADMIN_SLUG,
                'description' => 'Full access',
            ]);
            $role->id = ((int) Role::max('id')) + 1;
            $role->save();
        }

        return User::factory()->create(['role_id' => $role->id]);
    }
}
