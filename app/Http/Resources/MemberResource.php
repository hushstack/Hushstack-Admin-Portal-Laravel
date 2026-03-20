<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'position' => new PositionResource($this->whenLoaded('position')),
            'long_description' => $this->long_description,
            'skills' => $this->skills,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at?->format('M d Y'),
            'updated_at' => $this->updated_at?->format('M d Y'),
        ];
    }
}
