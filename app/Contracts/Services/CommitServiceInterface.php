<?php

namespace App\Contracts\Services;

use App\Models\Commit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Commit Service Interface
 *
 * OOAD: Service Layer Pattern - encapsulates business logic for Commits
 * Clean Architecture: Domain logic independent of frameworks
 */
interface CommitServiceInterface
{
    /**
     * Get all commits for a user with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function getAllForUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get commits by branch.
     */
    public function getByBranch(int $branchId, int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get commit by ID with user ownership check.
     */
    public function getById(int $id, int $userId): ?Commit;

    /**
     * Create new commit.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $userId): Commit;

    /**
     * Update commit.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data, int $userId): ?Commit;

    /**
     * Delete commit.
     */
    public function delete(int $id, int $userId): bool;

    /**
     * Verify branch ownership.
     */
    public function verifyBranchOwnership(int $branchId, int $userId): bool;

    /**
     * Get count statistics for user.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getCountStats(int $userId, ?int $branchId = null, ?int $collectionId = null): array;
}
