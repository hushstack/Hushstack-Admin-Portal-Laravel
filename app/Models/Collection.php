<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Collection Model
 *
 * Represents a project/repository container (e.g., Ecommerce, Mobile App)
 *
 * OOAD Patterns:
 * - Single Responsibility: Manages collection metadata only
 * - Association: Has many Branches
 * - Inverse Association: Belongs to User
 *
 * Security:
 * - User-scoped data isolation via user_id
 * - OWASP A01:2021 - Access Control enforced at query level
 *
 * Performance:
 * - Eager load relationships to prevent N+1
 * - Indexed user_id for fast user-scoped queries
 */
class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot model - Auto-generate slug from name if not provided
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($collection) {
            if (empty($collection->slug)) {
                $collection->slug = \Illuminate\Support\Str::slug($collection->name);
            }
        });
    }

    /**
     * Collection belongs to a User (Owner)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Collection has many Branches
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Scope: Filter by authenticated user (Security)
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Include branch count
     */
    public function scopeWithBranchCount($query)
    {
        return $query->withCount('branches');
    }

    /**
     * Scope: Include commit count through branches
     */
    public function scopeWithCommitCount($query)
    {
        return $query->withCount(['branches as commits_count' => function ($query) {
            $query->select(\DB::raw('coalesce(sum((select count(*) from commits where commits.branch_id = branches.id)), 0)'));
        }]);
    }
}
