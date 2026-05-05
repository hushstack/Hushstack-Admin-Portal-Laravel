<?php

namespace App\Http\Resources\NoteFlow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'content' => $this->content,
            'checked' => $this->checked,
            'sort_order' => $this->sort_order,
        ];
    }
}
