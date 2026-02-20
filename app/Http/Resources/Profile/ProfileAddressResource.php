<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class ProfileAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'country'     => $this->country,
            'city_state'  => $this->city_state,
            'postal_code' => $this->postal_code,
            'tax_id'      => $this->tax_id,
            'address'     => $this->address,
        ];
    }
}
