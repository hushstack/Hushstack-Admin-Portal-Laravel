<?php

namespace App\Http\Resources\CliAuth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CliAuthStartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'device_code' => $this['device_code'],
            'user_code' => $this['user_code'],
            'verification_uri' => $this['verification_uri'],
            'verification_uri_complete' => $this['verification_uri_complete'],
            'interval' => $this['interval'],
            'expires_in' => $this['expires_in'],
        ];
    }
}
