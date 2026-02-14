<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'is_verified' => (bool) $this->is_verified,
            'picture' => $this->picture,
            'cover' => $this->cover,
            'bio' => $this->bio,
            'note' => $this->note,
            'birth_of_date' => optional($this->birth_of_date)->toDateString(),
            'age' => $this->age,
            'nationality_id' => $this->nationality_id,
            'contact_url' => $this->contact_url,
            'address' => $this->address,
            'provider' => $this->provider,
            'email_verified_at' => optional($this->email_verified_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
