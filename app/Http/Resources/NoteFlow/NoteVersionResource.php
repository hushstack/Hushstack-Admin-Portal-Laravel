<?php

namespace App\Http\Resources\NoteFlow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'note_id' => $this->note_id,
            'title' => $this->title,
            'emoji' => $this->emoji,
            'content' => $this->content,
            'blocks_snapshot' => $this->blocks_snapshot,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
