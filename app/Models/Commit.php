<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Collection;

/**
 * Commit Model
 *
 * Represents a commit within a branch
 *
 * OOAD Patterns:
 * - Single Responsibility: Manages commit metadata and SHA
 * - Association: Belongs to Branch
 * - Inverse Association: Belongs to User
 *
 * Security:
 * - User-scoped data isolation via user_id
 * - SHA field for integrity verification
 * - OWASP A01:2021 - Access Control enforced at query level
 *
 * Performance:
 * - Indexed composite (user_id, branch_id) for fast lookups
 * - Indexed branch_id for commit listing
 */
class Commit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'name',
        'description',
        'sha',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Commit belongs to a Branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Commit belongs to a User (Owner)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get collection through branch (accessor for convenience)
     */
    public function getCollectionAttribute(): ?Collection
    {
        return $this->branch?->collection;
    }

    /**
     * Scope: Filter by authenticated user (Security)
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by branch
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope: Filter by collection (via branch)
     */
    public function scopeForCollection($query, int $collectionId)
    {
        return $query->whereHas('branch', function ($q) use ($collectionId) {
            $q->where('collection_id', $collectionId);
        });
    }
}
