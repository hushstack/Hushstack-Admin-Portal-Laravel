<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class ProfilePersonalInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'email'       => $this->email,
            'phone'       => $this->phone_number,
            'bio'         => $this->bio,
            'username'    => $this->username,
        ];
    }
}
