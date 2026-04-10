<?php

namespace App\Services;

use App\Contracts\Repositories\PermissionRepositoryInterface;
use App\Contracts\Services\PermissionServiceInterface;
use App\DTOs\Permission\CreatePermissionData;
use App\DTOs\Permission\UpdatePermissionData;
use App\Exceptions\DuplicatePermissionException;
use App\Exceptions\PermissionInUseException;
use App\Exceptions\PermissionNotFoundException;
use App\Exceptions\ProtectedPermissionException;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Permission Service
 *
 * OOAD: Service Layer Pattern - encapsulates business logic
 * Clean Architecture: Domain logic independent of frameworks
 */
class PermissionService implements PermissionServiceInterface
{
    /**
     * Protected system permissions that cannot be modified.
     *
     * @var array<string>
     */
    private const PROTECTED_SLUGS = ['super-admin', 'system-admin'];

    public function __construct(
        private readonly PermissionRepositoryInterface $repository,
        private readonly CacheService $cacheService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getAll(): Collection
    {
        return $this->cacheService->remember(
            'permissions:all',
            now()->addMinutes(10),
            fn () => $this->repository->getAllOrdered()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getById(int $id): Permission
    {
        $permission = $this->repository->findById($id);

        if ($permission === null) {
            throw PermissionNotFoundException::forId($id);
        }

        return $permission;
    }

    /**
     * {@inheritDoc}
     */
    public function getBySlug(string $slug): Permission
    {
        $permission = $this->repository->findBySlug($slug);

        if ($permission === null) {
            throw PermissionNotFoundException::forSlug($slug);
        }

        return $permission;
    }

    /**
     * {@inheritDoc}
     */
    public function create(CreatePermissionData $data): Permission
    {
        // Generate slug if not provided
        $slug = $data->slug ?? Str::slug($data->name);

        // Check for duplicates
        if ($this->repository->slugExists($slug)) {
            throw DuplicatePermissionException::forSlug($slug);
        }

        // Sanitize and limit strings
        $createData = [
            'name' => Str::limit($data->name, 80),
            'slug' => $slug,
            'description' => $data->description ? Str::limit($data->description, 255) : null,
        ];

        $permission = $this->repository->create($createData);

        $this->cacheService->forget('permissions:all');

        return $permission;
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, UpdatePermissionData $data): Permission
    {
        $permission = $this->repository->findById($id);

        if ($permission === null) {
            throw PermissionNotFoundException::forId($id);
        }

        // Check if protected
        if ($this->isProtected($permission) && $data->slug !== null) {
            throw ProtectedPermissionException::forModification($permission->slug);
        }

        // Check slug uniqueness if changing
        if ($data->slug !== null && $data->slug !== $permission->slug) {
            if ($this->repository->slugExists($data->slug, $permission->id)) {
                throw DuplicatePermissionException::forSlug($data->slug);
            }
        }

        if (!$data->hasData()) {
            return $permission;
        }

        $updateData = $data->toArray();

        // Sanitize strings
        if (isset($updateData['name'])) {
            $updateData['name'] = Str::limit($updateData['name'], 80);
        }
        if (isset($updateData['slug'])) {
            $updateData['slug'] = Str::slug($updateData['slug']);
        }
        if (isset($updateData['description'])) {
            $updateData['description'] = Str::limit($updateData['description'], 255);
        }

        $permission = $this->repository->update($permission, $updateData);

        $this->cacheService->forget('permissions:all');
        $this->cacheService->forget("permission:{$permission->slug}");

        return $permission;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): void
    {
        $permission = $this->repository->findById($id);

        if ($permission === null) {
            throw PermissionNotFoundException::forId($id);
        }

        // Check if protected
        if ($this->isProtected($permission)) {
            throw ProtectedPermissionException::forDeletion($permission->slug);
        }

        // Check if in use
        if ($this->repository->hasRoles($permission)) {
            $roleCount = $permission->roles()->count();
            throw PermissionInUseException::withRoleCount($permission->name, $roleCount);
        }

        $this->repository->delete($permission);

        $this->cacheService->forget('permissions:all');
        $this->cacheService->forget("permission:{$permission->slug}");
    }

    /**
     * {@inheritDoc}
     */
    public function isSlugAvailable(string $slug, ?int $excludeId = null): bool
    {
        return !$this->repository->slugExists($slug, $excludeId);
    }

    /**
     * Check if permission is protected.
     */
    private function isProtected(Permission $permission): bool
    {
        return in_array($permission->slug, self::PROTECTED_SLUGS, true);
    }
}
