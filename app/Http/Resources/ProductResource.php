<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'image' => $this->image,
            'price' => $this->price,
            'qty' => $this->qty,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'department' => $this->category->relationLoaded('department') ? [
                        'id' => $this->category->department->id,
                        'name' => $this->category->department->name,
                        'slug' => $this->category->department->slug,
                    ] : null,
                ];
            }),
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                    'slug' => $this->brand->slug,
                ];
            }),
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->userDisplayName(),
            ] : null,
            'is_stock' => $this->is_stock,
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
        $name = trim(($this->user->first_name ?? '') . ' ' . ($this->user->last_name ?? ''));
        return $name !== '' ? $name : ($this->user->username ?? $this->user->email ?? 'User');
    }
}
