<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Collection Resource
 *
 * Transforms Collection model for API response
 *
 * Security: Selective field exposure prevents data leakage
 * Performance: Minimal data transfer with essential fields only
 * Format: Dates as '10 Jan 2026 00:00 am/pm'
 */
class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,

            // Owner info (minimal)
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->userDisplayName(),
                ];
            }),

            // Counts when loaded (performance optimization)
            'branches_count' => $this->whenCounted('branches'),
            'commits_count' => $this->when(isset($this->commits_count), $this->commits_count),

            // Related data when loaded
            'branches' => BranchResource::collection($this->whenLoaded('branches')),

            // Dates formatted as '10 Jan 2026 00:00 am/pm'
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }

    /**
     * Format date as '10 Jan 2026 00:00 am/pm'
     */
    private function formatDate($value): ?string
    {
        return optional($value)->format('d M Y h:i a');
    }

    /**
     * Get user display name
     */
    private function userDisplayName(): string
    {
        $name = trim(($this->user->first_name ?? '').' '.($this->user->last_name ?? ''));

        return $name !== '' ? $name : ($this->user->username ?? $this->user->email ?? 'User');
    }
}
