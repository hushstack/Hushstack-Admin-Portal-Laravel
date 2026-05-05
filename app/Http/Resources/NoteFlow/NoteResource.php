<?php

namespace App\Http\Resources\NoteFlow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'emoji' => $this->emoji,
            'content' => $this->content,
            'folder_id' => $this->folder_id,
            'folder' => new FolderResource($this->whenLoaded('folder')),
            'is_favorite' => (bool) $this->is_favorite,
            'word_count' => (int) $this->word_count,
            'blocks' => NoteBlockResource::collection($this->whenLoaded('blocks')),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
