<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'source' => $this->source,
            'server_name' => $this->server_name,
            'device_name' => $this->device_name,
            'type' => $this->type,
            'severity' => $this->severity,
            'status' => $this->status,
            'title' => $this->title,
            'message' => $this->message,
            'source_ip' => $this->source_ip,
            'user_name' => $this->user_name,
            'auth_method' => $this->auth_method,
            'website_url' => $this->website_url,
            'service_name' => $this->service_name,
            'path' => $this->path,
            'fingerprint' => $this->fingerprint,
            'occurrence_count' => $this->occurrence_count,
            'first_seen_at' => $this->first_seen_at?->toAtomString(),
            'last_seen_at' => $this->last_seen_at?->toAtomString(),
            'acknowledged_at' => $this->acknowledged_at?->toAtomString(),
            'acknowledged_by' => $this->whenLoaded('acknowledgedBy', fn () => $this->userSummary($this->acknowledgedBy)),
            'resolved_at' => $this->resolved_at?->toAtomString(),
            'resolved_by' => $this->whenLoaded('resolvedBy', fn () => $this->userSummary($this->resolvedBy)),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }

    private function userSummary($user): ?array
    {
        if (! $user) {
            return null;
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return [
            'id' => $user->id,
            'name' => $name !== '' ? $name : ($user->username ?? $user->email),
            'email' => $user->email,
        ];
    }
}
