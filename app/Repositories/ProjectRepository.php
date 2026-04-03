<?php

namespace App\Repositories;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Project Repository
 *
 * Implements Repository Pattern for data access abstraction.
 * Provides clean separation between business logic and data access.
 *
 * OOAD: Repository Pattern - abstracts data persistence details
 * Clean Code: Single Responsibility - handles only data access
 */
class ProjectRepository
{
    /**
     * Constructor with dependency injection.
     *
     * Clean Code: Dependency injection for testability
     */
    public function __construct(
        private readonly Project $model
    ) {}

    /**
     * Get paginated projects with optional filters.
     *
     * Performance: Uses pagination to limit memory usage
     * Security: Validates and sanitizes filter inputs
     */
    public function getPaginated(
        ?string $search = null,
        ?bool $published = null,
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        $query->search($search);

        if ($published !== null) {
            $query->where('is_published', $published);
        }

        if (in_array($sortBy, ['id', 'title', 'created_at', 'updated_at', 'is_published'], true)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get all projects without pagination.
     *
     * Performance: Use with caution on large datasets
     */
    public function getAll(
        ?string $search = null,
        ?bool $published = null
    ): Collection {
        $query = $this->model->newQuery();

        $query->search($search);

        if ($published !== null) {
            $query->where('is_published', $published);
        }

        return $query->latest()->get();
    }

    /**
     * Find project by ID.
     *
     * Security: Returns null if not found (safe for route model binding)
     */
    public function findById(int $id): ?Project
    {
        return $this->model->find($id);
    }

    /**
     * Find project by ID or fail.
     *
     * Security: Throws 404 if not found
     */
    public function findOrFail(int $id): Project
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create new project.
     *
     * Security: Uses mass assignment protection
     */
    public function create(array $data): Project
    {
        return $this->model->create($data);
    }

    /**
     * Update existing project.
     *
     * Security: Uses mass assignment protection
     * Performance: Returns updated model to avoid re-querying
     * Fix: Only updates fields that are present in data
     */
    public function update(Project $project, array $data): Project
    {
        // Only update fields that are actually present in the data
        $fillable = $project->getFillable();
        $updateData = array_intersect_key($data, array_flip($fillable));

        if (!empty($updateData)) {
            $project->update($updateData);
        }

        return $project->fresh();
    }

    /**
     * Delete project (soft delete).
     *
     * Security: Soft delete prevents accidental data loss
     */
    public function delete(Project $project): bool
    {
        return $project->delete();
    }

    /**
     * Restore soft-deleted project.
     */
    public function restore(int $id): ?Project
    {
        $project = $this->model->withTrashed()->find($id);

        if ($project && $project->trashed()) {
            $project->restore();
            return $project;
        }

        return null;
    }

    /**
     * Force delete project permanently.
     *
     * Security: Use with caution - removes all data
     */
    public function forceDelete(Project $project): bool
    {
        return $project->forceDelete();
    }

    /**
     * Check if title exists (excluding current project for updates).
     *
     * Security: Prevents duplicate entries
     */
    public function titleExists(string $title, ?int $excludeId = null): bool
    {
        $query = $this->model->where('title', $title);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get recent projects with limit.
     *
     * Performance: Uses limit for memory efficiency
     */
    public function getRecent(int $limit = 5, ?bool $published = true): Collection
    {
        $query = $this->model->latest();

        if ($published !== null) {
            $query->where('is_published', $published);
        }

        return $query->limit($limit)->get();
    }
}
