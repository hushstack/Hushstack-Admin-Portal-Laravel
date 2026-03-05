<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'message' => $this->message,
            'type_req' => $this->type_req,
            'telegram_number' => $this->telegram_number,
            'submitted_at' => optional($this->submitted_at)->format('d M Y'),
        ];
    }
}
