<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Branch Model
 *
 * Represents a branch within a collection (e.g., main, develop, feature/*)
 *
 * OOAD Patterns:
 * - Single Responsibility: Manages branch metadata and status
 * - Association: Belongs to Collection, has many Commits
 * - Inverse Association: Belongs to User
 *
 * Security:
 * - User-scoped data isolation via user_id
 * - OWASP A01:2021 - Access Control enforced at query level
 *
 * Performance:
 * - Indexed composite (user_id, collection_id) for fast lookups
 * - Indexed stage for filtered queries
 */
class Branch extends Model
{
    use HasFactory;

    /**
     * Stage constants for type safety
     */
    public const STAGE_LOCAL = 'local';
    public const STAGE_DEV = 'dev';
    public const STAGE_STAGING = 'staging';
    public const STAGE_PVT = 'pvt';
    public const STAGE_PROD = 'prod';

    public const STAGES = [
        self::STAGE_LOCAL,
        self::STAGE_DEV,
        self::STAGE_STAGING,
        self::STAGE_PVT,
        self::STAGE_PROD,
    ];

    /**
     * Status constants for type safety
     */
    public const STATUS_PUSHED = 'pushed';
    public const STATUS_MERGED = 'merged';
    public const STATUS_PENDING = 'pending';

    public const STATUSES = [
        self::STATUS_PUSHED,
        self::STATUS_MERGED,
        self::STATUS_PENDING,
    ];

    protected $fillable = [
        'user_id',
        'collection_id',
        'name',
        'description',
        'status',
        'stage',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Branch belongs to a Collection
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Branch belongs to a User (Owner)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Branch has many Commits
     */
    public function commits(): HasMany
    {
        return $this->hasMany(Commit::class);
    }

    /**
     * Scope: Filter by authenticated user (Security)
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by collection
     */
    public function scopeForCollection($query, int $collectionId)
    {
        return $query->where('collection_id', $collectionId);
    }

    /**
     * Scope: Filter by stage
     */
    public function scopeByStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Include commit count
     */
    public function scopeWithCommitCount($query)
    {
        return $query->withCount('commits');
    }
}
