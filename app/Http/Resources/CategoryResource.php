<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image,
            'department' => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->userDisplayName(),
            ] : null,
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
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
