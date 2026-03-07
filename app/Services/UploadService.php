<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class UploadService
{
    public function uploadAndReplace($file, ?string $oldPathOrUrl, string $directory): string
    {
        $disk = Storage::disk('r2');

        if ($oldPathOrUrl) {
            $oldPath = $this->extractR2Path($oldPathOrUrl);
            if ($oldPath) {
                $disk->delete($oldPath);
            }
        }

        $path = $file->store($directory, 'r2');

        return $disk->url($path);
    }

    protected function extractR2Path(string $urlOrPath): string
    {
        $diskConfig = config('filesystems.disks.r2');
        $baseUrl = rtrim($diskConfig['url'] ?? '', '/');

        if ($baseUrl && str_starts_with($urlOrPath, $baseUrl)) {
            return ltrim(substr($urlOrPath, strlen($baseUrl)), '/');
        }

        return ltrim($urlOrPath, '/');
    }
}
