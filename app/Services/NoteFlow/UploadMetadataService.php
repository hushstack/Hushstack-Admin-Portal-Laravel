<?php

namespace App\Services\NoteFlow;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadMetadataService
{
    public function store(User $user, UploadedFile $file, array $data): Upload
    {
        $disk = $this->diskName();
        $path = $file->store('noteflow/uploads/'.$user->id, $disk);

        return Upload::create([
            'user_id' => $user->id,
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'extension' => strtolower($file->getClientOriginalExtension()),
            'size_bytes' => $file->getSize(),
            'status' => 'completed',
            'progress' => 100,
            'storage_path' => $path,
            'summary' => $data['generate_summary'] ?? false ? 'Summary generation provider is not configured yet.' : null,
            'processing_options' => [
                'ocr' => (bool) ($data['ocr'] ?? false),
                'generate_summary' => (bool) ($data['generate_summary'] ?? true),
                'searchable_index' => (bool) ($data['searchable_index'] ?? true),
                'extract_key_points' => (bool) ($data['extract_key_points'] ?? false),
            ],
            'output_format' => $data['output_format'] ?? 'Markdown',
        ]);
    }

    public function reprocess(Upload $upload): Upload
    {
        $upload->update([
            'status' => 'completed',
            'progress' => 100,
            'summary' => $upload->summary ?: 'Summary generation provider is not configured yet.',
        ]);

        return $upload->fresh();
    }

    public function deleteFile(Upload $upload): void
    {
        if ($upload->storage_path) {
            Storage::disk($this->diskName())->delete($upload->storage_path);
        }
    }

    private function diskName(): string
    {
        return (string) config('filesystems.default', 'local');
    }
}
