<?php

namespace App\Services\VersionControl;

use App\Contracts\Services\BranchServiceInterface;
use App\Models\Branch;
use App\Models\Collection;
use App\Models\Commit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Branch Service
 *
 * OOAD: Service Layer Pattern - encapsulates business logic
 * Clean Architecture: Domain logic independent of frameworks
 * Single Responsibility: Manages Branch business rules only
 */
class BranchService implements BranchServiceInterface
{
    public function __construct() {}

    /**
     * {@inheritDoc}
     */
    public function getAllForUser(int $userId, ?string $stage = null, int $perPage = 15): LengthAwarePaginator
    {
        return Branch::query()
            ->forUser($userId)
            ->when($stage && in_array($stage, Branch::STAGES), function ($query) use ($stage) {
                $query->byStage($stage);
            })
            ->with(['collection', 'user'])
            ->withCommitCount()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getByCollection(int $collectionId, int $userId, ?string $stage = null, int $perPage = 15): LengthAwarePaginator
    {
        return Branch::query()
            ->forUser($userId)
            ->forCollection($collectionId)
            ->when($stage && in_array($stage, Branch::STAGES), function ($query) use ($stage) {
                $query->byStage($stage);
            })
            ->with(['collection', 'user'])
            ->withCommitCount()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getById(int $id, int $userId): ?Branch
    {
        return Branch::query()
            ->forUser($userId)
            ->with(['collection', 'user'])
            ->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function getWithCommits(int $id, int $userId): ?Branch
    {
        return Branch::query()
            ->forUser($userId)
            ->with(['collection', 'user', 'commits'])
            ->withCommitCount()
            ->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data, int $userId): Branch
    {
        $data['user_id'] = $userId;

        $branch = Branch::create($data);
        $branch->load(['collection', 'user']);

        return $branch;
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data, int $userId): ?Branch
    {
        $branch = $this->getById($id, $userId);

        if (! $branch) {
            return null;
        }

        $branch->update($data);
        $branch->load(['collection', 'user']);

        return $branch;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id, int $userId): bool
    {
        $branch = $this->getById($id, $userId);

        if (! $branch) {
            return false;
        }

        return $branch->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function verifyCollectionOwnership(int $collectionId, int $userId): bool
    {
        return Collection::query()
            ->forUser($userId)
            ->where('id', $collectionId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getCountStats(int $userId, ?int $collectionId = null): array
    {
        $branchQuery = Branch::query()->forUser($userId);

        if ($collectionId) {
            $branchQuery->forCollection($collectionId);
        }

        // Count branches grouped by stage
        $branchesByStage = (clone $branchQuery)
            ->selectRaw('stage, COUNT(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        // Fill in missing stages with 0
        $stageCounts = array_fill_keys(Branch::STAGES, 0);
        $stageCounts = array_merge($stageCounts, $branchesByStage);

        // Total commits
        $commitQuery = Commit::query()->forUser($userId);
        if ($collectionId) {
            $commitQuery->forCollection($collectionId);
        }
        $totalCommits = $commitQuery->count();

        return [
            'branches_by_stage' => $stageCounts,
            'total_branches' => array_sum($stageCounts),
            'total_commits' => $totalCommits,
            'collection_id' => $collectionId,
        ];
    }
}
