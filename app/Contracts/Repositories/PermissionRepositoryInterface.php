<?php

namespace App\Contracts\Repositories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

/**
 * Permission Repository Interface
 *
 * OOAD: Repository Pattern Contract - defines the contract for data access
 * Clean Architecture: Dependency Inversion Principle - depends on abstraction
 */
interface PermissionRepositoryInterface
{
    /**
     * Get all permissions ordered by name.
     *
     * @return Collection<int, Permission>
     */
    public function getAllOrdered(): Collection;

    /**
     * Find permission by ID.
     */
    public function findById(int $id): ?Permission;

    /**
     * Find permission by slug.
     */
    public function findBySlug(string $slug): ?Permission;

    /**
     * Check if slug exists.
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool;

    /**
     * Create new permission.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Permission;

    /**
     * Update permission.
     *
     * @param array<string, mixed> $data
     */
    public function update(Permission $permission, array $data): Permission;

    /**
     * Delete permission.
     */
    public function delete(Permission $permission): bool;

    /**
     * Check if permission has any roles assigned.
     */
    public function hasRoles(Permission $permission): bool;

    /**
     * Get permissions by IDs.
     *
     * @param array<int> $ids
     * @return Collection<int, Permission>
     */
    public function getByIds(array $ids): Collection;
}
