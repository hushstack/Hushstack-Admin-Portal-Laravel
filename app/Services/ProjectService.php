<?php

namespace App\Services;

use App\Models\Project;
use App\Repositories\ProjectRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Project Service
 *
 * Business logic layer for project operations.
 * Implements Service Pattern for clean architecture.
 *
 * OOAD: Service Layer Pattern - encapsulates business logic
 * Clean Code: Separates business logic from data access and presentation
 * Laravel Performance: Implements transaction management and caching hooks
 */
class ProjectService
{
    /**
     * Image storage directory path.
     */
    private const IMAGE_DIRECTORY = 'projects';

    /**
     * Constructor with dependency injection.
     *
     * Clean Code: Dependencies injected for testability and loose coupling
     */
    public function __construct(
        private readonly ProjectRepository $repository,
        private readonly UploadService $uploadService
    ) {}

    /**
     * Get paginated projects.
     *
     * Performance: Uses pagination to prevent memory exhaustion
     * Clean Code: Delegates data access to repository
     */
    public function getPaginated(
        ?string $search = null,
        ?bool $published = null,
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->repository->getPaginated(
            search: $search,
            published: $published,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
            perPage: $perPage
        );
    }

    /**
     * Get all projects without pagination.
     */
    public function getAll(
        ?string $search = null,
        ?bool $published = null
    ): Collection {
        return $this->repository->getAll(
            search: $search,
            published: $published
        );
    }

    /**
     * Get single project by ID.
     */
    public function findById(int $id): ?Project
    {
        return $this->repository->findById($id);
    }

    /**
     * Create new project with optional image upload.
     *
     * Security: Wraps in transaction for data integrity
     * OWASP: A01:2021 - Transaction ensures atomic operations
     * Performance: Single database transaction reduces lock time
     */
    public function create(array $data, ?UploadedFile $image = null): Project
    {
        return DB::transaction(function () use ($data, $image): Project {
            // Handle image upload if provided
            if ($image !== null) {
                $data['image'] = $this->handleImageUpload($image);
            }

            // Sanitize URL input (Security: XSS prevention)
            if (!empty($data['url'])) {
                $data['url'] = $this->sanitizeUrl($data['url']);
            }

            // Normalize technologies to array
            if (isset($data['technologies']) && is_string($data['technologies'])) {
                $data['technologies'] = $this->parseTechnologies($data['technologies']);
            }

            return $this->repository->create($data);
        });
    }

    /**
     * Update existing project with optional image replacement.
     *
     * Security: Transaction ensures data consistency
     * OWASP: A05:2021 - Security Misconfiguration prevention
     */
    public function update(Project $project, array $data, ?UploadedFile $image = null): Project
    {
        return DB::transaction(function () use ($project, $data, $image): Project {
            // Handle image replacement if new image provided
            if ($image !== null) {
                $data['image'] = $this->handleImageReplace($image, $project->image);
            }

            // Sanitize URL input
            if (!empty($data['url'])) {
                $data['url'] = $this->sanitizeUrl($data['url']);
            }

            // Normalize technologies to array
            if (isset($data['technologies'])) {
                if (is_string($data['technologies'])) {
                    $data['technologies'] = $this->parseTechnologies($data['technologies']);
                } elseif (is_array($data['technologies'])) {
                    $data['technologies'] = array_values(array_filter($data['technologies']));
                }
            }

            return $this->repository->update($project, $data);
        });
    }

    /**
     * Delete project (soft delete).
     *
     * Security: Soft delete preserves data for recovery
     * Performance: Uses transaction for consistency
     */
    public function delete(Project $project): bool
    {
        return DB::transaction(function () use ($project): bool {
            return $this->repository->delete($project);
        });
    }

    /**
     * Restore soft-deleted project.
     */
    public function restore(int $id): ?Project
    {
        return $this->repository->restore($id);
    }

    /**
     * Force delete project permanently with cleanup.
     *
     * Security: Permanent removal - use with caution
     */
    public function forceDelete(Project $project): bool
    {
        return DB::transaction(function () use ($project): bool {
            return $this->repository->forceDelete($project);
        });
    }

    /**
     * Handle image upload.
     *
     * Security: Validates file type and size before storage
     * OWASP: A04:2021 - Insecure Design prevention
     * Performance: Delegates to dedicated upload service
     */
    private function handleImageUpload(UploadedFile $image): string
    {
        $this->validateImage($image);

        return $this->uploadService->uploadAndReplace(
            file: $image,
            oldPathOrUrl: null,
            directory: self::IMAGE_DIRECTORY
        );
    }

    /**
     * Handle image replacement (delete old, upload new).
     *
     * Security: Validates new file before replacing old
     */
    private function handleImageReplace(UploadedFile $image, ?string $oldImage): string
    {
        $this->validateImage($image);

        return $this->uploadService->uploadAndReplace(
            file: $image,
            oldPathOrUrl: $oldImage,
            directory: self::IMAGE_DIRECTORY
        );
    }

    /**
     * Validate uploaded image.
     *
     * Security: OWASP A04:2021 - File upload restrictions
     * - File type validation
     * - File size limitation
     * - MIME type verification
     */
    private function validateImage(UploadedFile $image): void
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($image->getMimeType(), $allowedMimeTypes, true)) {
            throw new \InvalidArgumentException(
                'Invalid image type. Allowed: JPEG, PNG, GIF, WebP, SVG.'
            );
        }

        if ($image->getSize() > $maxSize) {
            throw new \InvalidArgumentException(
                'Image size exceeds maximum allowed (5MB).'
            );
        }
    }

    /**
     * Sanitize and validate URL.
     *
     * Security: OWASP A03:2021 - Injection prevention
     * - URL validation
     * - Protocol restriction
     */
    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);

        // Ensure URL has valid protocol
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL format provided.');
        }

        // Additional security: check for allowed protocols only
        $parsed = parse_url($url);
        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Only HTTP and HTTPS URLs are allowed.');
        }

        return $url;
    }

    /**
     * Parse technologies string into array.
     *
     * Clean Code: Centralized parsing logic
     */
    private function parseTechnologies(string $technologies): array
    {
        if (empty($technologies)) {
            return [];
        }

        // Support comma-separated or newline-separated values
        $delimiters = [',', "\n", "\r"];
        $values = str_replace($delimiters, ',', $technologies);

        return array_values(array_filter(array_map(
            fn ($tech) => trim($tech),
            explode(',', $values)
        )));
    }

    /**
     * Check for duplicate title.
     *
     * Business Rule: Prevent duplicate project titles
     */
    public function isDuplicateTitle(string $title, ?int $excludeId = null): bool
    {
        return $this->repository->titleExists($title, $excludeId);
    }

    /**
     * Get recent projects.
     *
     * Performance: Limited result set for homepage/display usage
     */
    public function getRecent(int $limit = 5, ?bool $published = true): Collection
    {
        return $this->repository->getRecent($limit, $published);
    }
}
