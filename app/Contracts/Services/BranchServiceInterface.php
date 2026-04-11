<?php

namespace App\Contracts\Services;

use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Branch Service Interface
 *
 * OOAD: Service Layer Pattern - encapsulates business logic for Branches
 * Clean Architecture: Domain logic independent of frameworks
 */
interface BranchServiceInterface
{
    /**
     * Get all branches for a user with optional filters.
     */
    public function getAllForUser(int $userId, ?string $stage = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get branches by collection with optional stage filter.
     */
    public function getByCollection(int $collectionId, int $userId, ?string $stage = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get branch by ID with user ownership check.
     */
    public function getById(int $id, int $userId): ?Branch;

    /**
     * Get branch with commits loaded.
     */
    public function getWithCommits(int $id, int $userId): ?Branch;

    /**
     * Create new branch.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $userId): Branch;

    /**
     * Update branch.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data, int $userId): ?Branch;

    /**
     * Delete branch.
     */
    public function delete(int $id, int $userId): bool;

    /**
     * Verify collection ownership.
     */
    public function verifyCollectionOwnership(int $collectionId, int $userId): bool;

    /**
     * Get count statistics for user.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getCountStats(int $userId, ?int $collectionId = null): array;
}
