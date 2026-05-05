<?php

namespace App\Http\Resources\NoteFlow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationDeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'notification_id' => $this->notification_id,
            'title' => $this->title,
            'sent_to' => $this->sent_to,
            'sent_at' => optional($this->sent_at)->toISOString(),
            'status' => $this->status,
            'error_message' => $this->error_message,
        ];
    }
}
