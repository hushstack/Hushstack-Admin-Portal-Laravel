<?php

namespace App\Http\Resources\NoteFlow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this['user'];
        $settings = $this['settings'];

        return [
            'profile' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'bio' => $user->bio,
                'avatar_url' => $user->picture,
            ],
            'appearance' => $settings->appearance,
            'email_notifications' => $settings->email_notifications,
            'push_notifications' => $settings->push_notifications,
            'ai' => $settings->ai,
        ];
    }
}
