<?php

namespace App\Services;

use App\Models\Position;
use Illuminate\Support\Str;

class PositionService
{
    public function __construct(private readonly UploadService $uploadService) {}

    public function getAll(int $perPage = 15)
    {
        return Position::query()
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data, $imageFile = null)
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        if ($imageFile) {
            $data['image'] = $this->uploadService->uploadAndReplace(
                $imageFile,
                null,
                'positions/images'
            );
        }

        return Position::create($data);
    }

    public function update(Position $position, array $data, $imageFile = null)
    {
        if (! array_key_exists('slug', $data) && array_key_exists('name', $data)) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($imageFile) {
            $data['image'] = $this->uploadService->uploadAndReplace(
                $imageFile,
                $position->image,
                'positions/images'
            );
        }

        $position->update($data);

        return $position;
    }

    public function delete(Position $position)
    {
        // Maybe delete image from R2 as well if needed
        return $position->delete();
    }
}
