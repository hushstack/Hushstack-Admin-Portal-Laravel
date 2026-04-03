<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Project Model
 *
 * Represents a project entity with soft deletes support.
 * Implements security best practices and performance optimizations.
 */
class Project extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * Security: Only explicitly defined fields can be mass assigned
     * OWASP: A01:2021 – Broken Access Control prevention
     */
    protected $fillable = [
        'title',
        'description',
        'image',
        'url',
        'technologies',
        'is_published',
    ];

    /**
     * The attributes that should be cast.
     *
     * Performance: Proper type casting reduces memory usage
     * Security: Boolean casting prevents type juggling attacks
     */
    protected $casts = [
        'technologies' => 'array',
        'is_published' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * Security: Prevent sensitive/internal fields from exposure
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Boot the model.
     *
     * Performance: Register model event listeners for cleanup
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Project $project): void {
            if ($project->isForceDeleting()) {
                $project->deleteImage();
            }
        });
    }

    /**
     * Scope: Get only published projects.
     *
     * Performance: Database-level filtering reduces memory usage
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope: Get only unpublished projects.
     */
    public function scopeUnpublished($query)
    {
        return $query->where('is_published', false);
    }

    /**
     * Scope: Order by latest.
     *
     * Performance: Index on created_at recommended
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Search by title or description.
     *
     * Security: Uses parameterized queries to prevent SQL injection
     * Performance: Full-text index recommended for large datasets
     */
    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search): void {
            $q->where('title', 'like', '%' . $search . '%')
              ->orWhere('description', 'like', '%' . $search . '%');
        });
    }

    /**
     * Delete the associated image from storage.
     *
     * Security: Validates path before deletion
     * Clean Code: Single responsibility method
     */
    public function deleteImage(): void
    {
        if (empty($this->image)) {
            return;
        }

        $path = $this->extractStoragePath($this->image);

        if ($path && Storage::disk('r2')->exists($path)) {
            Storage::disk('r2')->delete($path);
        }
    }

    /**
     * Extract storage path from full URL.
     *
     * Security: Validates URL belongs to configured storage
     */
    protected function extractStoragePath(string $url): ?string
    {
        $diskConfig = config('filesystems.disks.r2');
        $baseUrl = rtrim($diskConfig['url'] ?? '', '/');

        if ($baseUrl && str_starts_with($url, $baseUrl)) {
            return ltrim(substr($url, strlen($baseUrl)), '/');
        }

        return null;
    }

    /**
     * Get technologies as formatted string.
     *
     * Clean Code: Accessor for presentation logic
     */
    public function getTechnologiesStringAttribute(): ?string
    {
        if (empty($this->technologies)) {
            return null;
        }

        return implode(', ', $this->technologies);
    }
}
