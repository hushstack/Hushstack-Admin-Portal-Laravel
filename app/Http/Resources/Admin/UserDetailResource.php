<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
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
            'picture' => $this->picture,
            'cover' => $this->cover,
            'birth_of_date' => optional($this->birth_of_date)->toDateString(),
            'nationality_id' => $this->nationality_id,
            'contact_url' => $this->contact_url,
            'address' => $this->address,
            'provider' => $this->provider,
            'created_at' => optional($this->created_at)->format('d M Y'),
            'facebook_url' => $this->facebook_url,
            'x_url' => $this->x_url,
            'linkedin_url' => $this->linkedin_url,
            'instagram_url' => $this->instagram_url,
            'country' => $this->country,
            'city_state' => $this->city_state,
            'role_id' => $this->role_id,
            'role' => $this->whenLoaded('role', function () {
                $role = $this->role;
                return $role ? [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                ] : null;
            }),
            'nationality' => $this->whenLoaded('nationality', function () {
                $nationality = $this->nationality;
                return $nationality ? [
                    'id' => $nationality->id,
                    'name' => $nationality->name ?? null,
                ] : null;
            }),
        ];
    }
}
