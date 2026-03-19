<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class ProfileHeaderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locationParts = array_filter([
            $this->city_state,
            $this->country,
        ]);

        return [
            'id' => $this->id,
            'bio' => $this->bio,
            'location' => $locationParts ? implode(', ', $locationParts) : null,
            'picture' => $this->picture,
            'cover' => $this->cover,
            'social_links' => [
                'facebook' => $this->facebook_url,
                'x' => $this->x_url,
                'linkedin' => $this->linkedin_url,
                'instagram' => $this->instagram_url,
            ],
        ];
    }
}
