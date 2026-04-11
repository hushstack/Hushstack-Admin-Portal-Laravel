<?php

namespace App\Services\VersionControl;

use App\Contracts\Services\CollectionServiceInterface;
use App\Models\Branch;
use App\Models\Collection;
use App\Models\Commit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Collection Service
 *
 * OOAD: Service Layer Pattern - encapsulates business logic
 * Clean Architecture: Domain logic independent of frameworks
 * Single Responsibility: Manages Collection business rules only
 */
class CollectionService implements CollectionServiceInterface
{
    public function __construct() {}

    /**
     * {@inheritDoc}
     */
    public function getAllForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Collection::query()
            ->forUser($userId)
            ->withBranchCount()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getById(int $id, int $userId): ?Collection
    {
        return Collection::query()
            ->forUser($userId)
            ->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function getWithBranches(int $id, int $userId): ?Collection
    {
        return Collection::query()
            ->forUser($userId)
            ->with(['branches' => function ($query) {
                $query->withCount('commits');
            }])
            ->withBranchCount()
            ->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data, int $userId): Collection
    {
        $data['user_id'] = $userId;
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $collection = Collection::create($data);

        return $collection->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data, int $userId): ?Collection
    {
        $collection = $this->getById($id, $userId);

        if (! $collection) {
            return null;
        }

        // Auto-generate slug if name changed but slug not provided
        if (! isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $collection->update($data);

        return $collection->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id, int $userId): bool
    {
        $collection = $this->getById($id, $userId);

        if (! $collection) {
            return false;
        }

        // Cascade delete handled by database foreign key constraints
        return $collection->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function slugExistsForUser(string $slug, int $userId, ?int $excludeId = null): bool
    {
        $query = Collection::query()
            ->forUser($userId)
            ->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getCountStats(int $userId): array
    {
        $totalCollections = Collection::query()
            ->forUser($userId)
            ->count();

        // Count branches grouped by stage
        $branchesByStage = Branch::query()
            ->forUser($userId)
            ->selectRaw('stage, COUNT(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        // Fill in missing stages with 0
        $stageCounts = array_fill_keys(Branch::STAGES, 0);
        $stageCounts = array_merge($stageCounts, $branchesByStage);

        $totalCommits = Commit::query()
            ->forUser($userId)
            ->count();

        return [
            'total_collections' => $totalCollections,
            'branches_by_stage' => $stageCounts,
            'total_branches' => array_sum($stageCounts),
            'total_commits' => $totalCommits,
        ];
    }
}
