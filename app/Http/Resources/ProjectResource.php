<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Project Resource
 *
 * Transforms Project model into a standardized API response format.
 *
 * Clean Code: Separates presentation logic from domain model
 * Performance: Conditional inclusion reduces response size
 */
class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource === null) {
            return [];
        }

        return [
            'id' => $this->id,

            'title' => $this->title,

            'description' => $this->description,

            'image' => $this->when(
                !empty($this->image),
                $this->image
            ),

            'image_url' => $this->when(
                !empty($this->image),
                $this->image
            ),

            'url' => $this->when(
                !empty($this->url),
                $this->url
            ),

            'technologies' => $this->technologies ?? [],

            'technologies_string' => $this->when(
                !empty($this->technologies),
                $this->technologies_string
            ),

            'is_published' => (bool) $this->is_published,

            'publication_status' => $this->is_published ? 'published' : 'draft',

            'created_at' => $this->formatDate($this->created_at),

            'updated_at' => $this->formatDate($this->updated_at),

            // Include soft delete info for admin views
            'deleted_at' => $this->when(
                $request->user()?->role?->name === 'admin' && $this->deleted_at,
                $this->formatDate($this->deleted_at)
            ),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'resource_type' => 'project',
                'api_version' => 'v1',
            ],
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     */
    public function withResponse(Request $request, \Illuminate\Http\JsonResponse $response): void
    {
        // Security headers
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'DENY');
    }

    /**
     * Format date consistently.
     *
     * Clean Code: Centralized formatting logic
     */
    private function formatDate(?\Carbon\Carbon $date): ?string
    {
        return $date?->format('Y-m-d H:i:s');
    }
}
