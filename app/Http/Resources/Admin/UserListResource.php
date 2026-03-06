<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'picture' => $this->picture,
            'role_id' => $this->role_id,
            'role' => $this->whenLoaded('role', function () {
                $role = $this->role;
                return $role ? [
                    'id' => $role->id,
                    'name' => $role->name,
                ] : null;
            }),
            'provider' => $this->provider,
            'is_verified' => (bool) $this->is_verified,
        ];
    }
}
