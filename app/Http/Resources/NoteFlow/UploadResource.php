<?php

namespace App\Http\Resources\NoteFlow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UploadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size_bytes' => (int) $this->size_bytes,
            'status' => $this->status,
            'progress' => (int) $this->progress,
            'summary' => $this->summary,
            'processing_options' => $this->processing_options,
            'output_format' => $this->output_format,
            'download_url' => $this->storage_path
                ? Storage::disk((string) config('filesystems.default', 'local'))->url($this->storage_path)
                : null,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
