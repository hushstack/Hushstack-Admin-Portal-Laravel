<?php

namespace App\Contracts\Services;

use App\Models\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Collection Service Interface
 *
 * OOAD: Service Layer Pattern - encapsulates business logic for Collections
 * Clean Architecture: Domain logic independent of frameworks
 */
interface CollectionServiceInterface
{
    /**
     * Get all collections for a user with pagination.
     */
    public function getAllForUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get collection by ID with user ownership check.
     */
    public function getById(int $id, int $userId): ?Collection;

    /**
     * Get collection with branches loaded.
     */
    public function getWithBranches(int $id, int $userId): ?Collection;

    /**
     * Create new collection.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $userId): Collection;

    /**
     * Update collection.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data, int $userId): ?Collection;

    /**
     * Delete collection.
     */
    public function delete(int $id, int $userId): bool;

    /**
     * Check if slug exists for user.
     */
    public function slugExistsForUser(string $slug, int $userId, ?int $excludeId = null): bool;

    /**
     * Get count statistics for user.
     *
     * @return array<string, mixed>
     */
    public function getCountStats(int $userId): array;
}
