<?php

namespace App\Repositories;

use App\Contracts\Repositories\PermissionRepositoryInterface;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

/**
 * Permission Repository
 *
 * OOAD: Repository Pattern Implementation
 * Clean Architecture: Data access abstraction
 */
class PermissionRepository implements PermissionRepositoryInterface
{
    public function __construct(
        private readonly Permission $model
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getAllOrdered(): Collection
    {
        return $this->model
            ->newQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'created_at', 'updated_at']);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?Permission
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlug(string $slug): ?Permission
    {
        return $this->model
            ->where('slug', $slug)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = $this->model->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Permission
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Permission $permission, array $data): Permission
    {
        $permission->update($data);
        return $permission->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Permission $permission): bool
    {
        return $permission->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function hasRoles(Permission $permission): bool
    {
        return $permission->roles()->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getByIds(array $ids): Collection
    {
        return $this->model
            ->whereIn('id', $ids)
            ->get();
    }
}
