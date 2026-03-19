<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image,
            'description' => $this->description,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->userDisplayName(),
            ] : null,
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
        ];
    }

    private function formatDate($value): ?string
    {
        return optional($value)->format('M d Y');
    }

    private function userDisplayName(): string
    {
        $name = trim(($this->user->first_name ?? '').' '.($this->user->last_name ?? ''));

        return $name !== '' ? $name : ($this->user->username ?? $this->user->email ?? 'User');
    }
}
