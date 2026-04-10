<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Permission Resource
 * 
 * Security: Selective field exposure prevents data leakage.
 * Performance: Minimal data transfer with essential fields only.
 */
class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            // Include roles count when loaded (performance optimization)
            'roles_count' => $this->whenCounted('roles'),
            // Include related roles if loaded
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            // Timestamps for audit purposes
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
