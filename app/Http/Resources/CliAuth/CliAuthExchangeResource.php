<?php

namespace App\Http\Resources\CliAuth;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CliAuthExchangeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this['access_token'],
            'token_type' => 'Bearer',
            'expires_at' => optional($this['expires_at'])->toAtomString(),
            'user' => new UserResource($this['user']),
        ];
    }
}
