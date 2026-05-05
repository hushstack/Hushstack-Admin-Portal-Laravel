<?php

namespace App\Http\Resources\NoteFlow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AiGenerationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tool' => $this->tool,
            'title' => $this->title,
            'input_text' => $this->input_text,
            'output_text' => $this->output_text,
            'preview' => Str::limit((string) $this->output_text, 160),
            'language' => $this->language,
            'tone' => $this->tone,
            'output_length' => $this->output_length,
            'source_note_id' => $this->source_note_id,
            'source_upload_id' => $this->source_upload_id,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
