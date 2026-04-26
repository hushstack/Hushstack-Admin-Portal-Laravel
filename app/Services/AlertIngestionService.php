<?php

namespace App\Services;

use App\Models\Alert;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AlertIngestionService
{
    public function ingest(array $data): array
    {
        $seenAt = isset($data['occurred_at'])
            ? Carbon::parse($data['occurred_at'])
            : now();

        $fingerprint = $this->fingerprint($data);

        return DB::transaction(function () use ($data, $seenAt, $fingerprint): array {
            $existing = Alert::query()
                ->where('fingerprint', $fingerprint)
                ->where('status', Alert::STATUS_OPEN)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->fill($this->latestFields($data, $seenAt));
                $existing->occurrence_count++;
                $existing->last_seen_at = $seenAt;
                $existing->save();

                return ['alert' => $existing->fresh(), 'created' => false];
            }

            $alert = Alert::create(array_merge(
                $this->latestFields($data, $seenAt),
                [
                    'source' => Alert::SOURCE_CACHEWRAITH,
                    'status' => Alert::STATUS_OPEN,
                    'fingerprint' => $fingerprint,
                    'occurrence_count' => 1,
                    'first_seen_at' => $seenAt,
                    'last_seen_at' => $seenAt,
                ]
            ));

            return ['alert' => $alert, 'created' => true];
        });
    }

    public function fingerprint(array $data): string
    {
        if (! empty($data['fingerprint'])) {
            return (string) $data['fingerprint'];
        }

        $target = $data['source_ip']
            ?? $data['website_url']
            ?? $data['service_name']
            ?? $data['path']
            ?? 'unknown';

        $raw = implode(':', [
            $data['type'] ?? 'unknown',
            $data['server_name'] ?? 'unknown',
            $target,
        ]);

        $fingerprint = Str::of($raw)
            ->lower()
            ->replaceMatches('/\s+/', '-')
            ->replaceMatches('/[^a-z0-9:._\-\/]/', '')
            ->toString();

        if (strlen($fingerprint) > 255) {
            return 'cachewraith:'.hash('sha256', $fingerprint);
        }

        return $fingerprint;
    }

    private function latestFields(array $data, Carbon $seenAt): array
    {
        return [
            'server_name' => $data['server_name'],
            'device_name' => $data['device_name'] ?? null,
            'type' => $data['type'],
            'severity' => $data['severity'],
            'title' => $data['title'],
            'message' => $data['message'] ?? null,
            'source_ip' => $data['source_ip'] ?? null,
            'user_name' => $data['user_name'] ?? null,
            'auth_method' => $data['auth_method'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'service_name' => $data['service_name'] ?? null,
            'path' => $data['path'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'last_seen_at' => $seenAt,
        ];
    }
}
