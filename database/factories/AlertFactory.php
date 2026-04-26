<?php

namespace Database\Factories;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        $seenAt = fake()->dateTimeBetween('-1 day');
        $type = fake()->randomElement(['ssh_bruteforce', 'service_down', 'website_down']);
        $server = fake()->word();
        $sourceIp = fake()->ipv4();

        return [
            'uuid' => (string) Str::uuid(),
            'source' => Alert::SOURCE_CACHEWRAITH,
            'server_name' => $server,
            'device_name' => $server,
            'type' => $type,
            'severity' => fake()->randomElement(Alert::SEVERITIES),
            'status' => Alert::STATUS_OPEN,
            'title' => fake()->sentence(4),
            'message' => fake()->sentence(),
            'source_ip' => $sourceIp,
            'fingerprint' => "{$type}:{$server}:{$sourceIp}",
            'occurrence_count' => 1,
            'first_seen_at' => $seenAt,
            'last_seen_at' => $seenAt,
            'metadata' => ['sample' => true],
        ];
    }
}
