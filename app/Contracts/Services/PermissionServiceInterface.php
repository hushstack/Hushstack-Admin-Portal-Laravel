<?php

namespace App\Contracts\Services;

use App\DTOs\Permission\CreatePermissionData;
use App\DTOs\Permission\UpdatePermissionData;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

/**
 * Permission Service Interface
 *
 * OOAD: Service Layer Pattern - encapsulates business logic
 * Clean Architecture: Business rules independent of frameworks
 */
interface PermissionServiceInterface
{
    /**
     * Get all permissions.
     *
     * @return Collection<int, Permission>
     */
    public function getAll(): Collection;

    /**
     * Get permission by ID.
     *
     * @throws \App\Exceptions\PermissionNotFoundException
     */
    public function getById(int $id): Permission;

    /**
     * Get permission by slug.
     *
     * @throws \App\Exceptions\PermissionNotFoundException
     */
    public function getBySlug(string $slug): Permission;

    /**
     * Create new permission.
     *
     * @throws \App\Exceptions\DuplicatePermissionException
     */
    public function create(CreatePermissionData $data): Permission;

    /**
     * Update permission.
     *
     * @throws \App\Exceptions\PermissionNotFoundException
     * @throws \App\Exceptions\DuplicatePermissionException
     * @throws \App\Exceptions\ProtectedPermissionException
     */
    public function update(int $id, UpdatePermissionData $data): Permission;

    /**
     * Delete permission.
     *
     * @throws \App\Exceptions\PermissionNotFoundException
     * @throws \App\Exceptions\ProtectedPermissionException
     * @throws \App\Exceptions\PermissionInUseException
     */
    public function delete(int $id): void;

    /**
     * Check if slug is available.
     */
    public function isSlugAvailable(string $slug, ?int $excludeId = null): bool;
}
