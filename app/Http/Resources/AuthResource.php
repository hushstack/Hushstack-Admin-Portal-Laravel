<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function __construct(private string $token, private $user)
    {
        parent::__construct($user);
    }

    public function toArray($request): array
    {
        return [
            'token' => $this->token,
            'token_type' => 'Bearer',
            'user' => new UserResource($this->user),
        ];
    }
}
