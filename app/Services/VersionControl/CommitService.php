<?php

namespace App\Services\VersionControl;

use App\Contracts\Services\CommitServiceInterface;
use App\Models\Branch;
use App\Models\Commit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Commit Service
 *
 * OOAD: Service Layer Pattern - encapsulates business logic
 * Clean Architecture: Domain logic independent of frameworks
 * Single Responsibility: Manages Commit business rules only
 */
class CommitService implements CommitServiceInterface
{
    public function __construct() {}

    /**
     * {@inheritDoc}
     */
    public function getAllForUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $branchId = $filters['branch_id'] ?? null;
        $collectionId = $filters['collection_id'] ?? null;

        return Commit::query()
            ->forUser($userId)
            ->when($branchId, function ($query, $branchId) {
                $query->forBranch($branchId);
            })
            ->when($collectionId, function ($query, $collectionId) {
                $query->forCollection($collectionId);
            })
            ->with(['branch.collection', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getByBranch(int $branchId, int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Commit::query()
            ->forUser($userId)
            ->forBranch($branchId)
            ->with(['branch.collection', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getById(int $id, int $userId): ?Commit
    {
        return Commit::query()
            ->forUser($userId)
            ->with(['branch.collection', 'user'])
            ->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data, int $userId): Commit
    {
        $data['user_id'] = $userId;

        $commit = Commit::create($data);
        $commit->load(['branch.collection', 'user']);

        return $commit;
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data, int $userId): ?Commit
    {
        $commit = Commit::query()
            ->forUser($userId)
            ->find($id);

        if (! $commit) {
            return null;
        }

        $commit->update($data);
        $commit->load(['branch.collection', 'user']);

        return $commit;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id, int $userId): bool
    {
        $commit = Commit::query()
            ->forUser($userId)
            ->find($id);

        if (! $commit) {
            return false;
        }

        return $commit->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function verifyBranchOwnership(int $branchId, int $userId): bool
    {
        return Branch::query()
            ->forUser($userId)
            ->where('id', $branchId)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getCountStats(int $userId, ?int $branchId = null, ?int $collectionId = null): array
    {
        $query = Commit::query()->forUser($userId);

        if ($branchId) {
            $query->forBranch($branchId);
        }

        if ($collectionId) {
            $query->forCollection($collectionId);
        }

        $totalCommits = $query->count();

        // Get breakdown by branch if no specific branch filter
        $commitsByBranch = [];
        if (! $branchId) {
            $branchQuery = Commit::query()
                ->forUser($userId)
                ->when($collectionId, function ($q) use ($collectionId) {
                    $q->forCollection($collectionId);
                })
                ->selectRaw('branch_id, COUNT(*) as count')
                ->groupBy('branch_id')
                ->pluck('count', 'branch_id')
                ->toArray();

            $commitsByBranch = $branchQuery;
        }

        return [
            'total_commits' => $totalCommits,
            'commits_by_branch' => $commitsByBranch,
            'branch_id' => $branchId,
            'collection_id' => $collectionId,
        ];
    }
}
