<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'first_name' => $this->user?->first_name,
            'last_name'  => $this->user?->last_name,
            'username'   => $this->user?->username,
            'email'      => $this->user?->email,
            'picture'    => $this->user?->picture,
            'role_id'    => $this->role_id,
            'role'       => $this->role?->name,
            'activity'   => $this->activity,
            'ip_address' => $this->ip_address,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
