<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            // 'created_at' => optional($this->created_at)->toISOString(),
            // 'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
