<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
    ];

    /**
     * The roles that have this permission.
     * Many-to-many relationship with Role model.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Find permission by slug.
     * Performance: Uses indexed slug column.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::query()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Check if permission is assigned to any role.
     * Security: Prevent deletion of permissions in use.
     */
    public function isAssigned(): bool
    {
        return $this->roles()->exists();
    }

    /**
     * Get cached permission by slug for performance.
     */
    public static function getCachedBySlug(string $slug): ?self
    {
        return cache()->remember(
            "permission:{$slug}",
            now()->addMinutes(10),
            fn () => static::findBySlug($slug)
        );
    }

    /**
     * Clear permission cache.
     */
    public static function clearCache(string $slug): void
    {
        cache()->forget("permission:{$slug}");
    }
}
