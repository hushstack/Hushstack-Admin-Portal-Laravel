<?php

namespace App\Http\Resources\CliAuth;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CliAuthMeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this['user']),
            'token' => [
                'name' => $this['token_name'],
                'abilities' => $this['abilities'],
                'expires_at' => optional($this['expires_at'])->toAtomString(),
            ],
        ];
    }
}
