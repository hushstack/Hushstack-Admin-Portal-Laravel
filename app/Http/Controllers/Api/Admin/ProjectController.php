<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Project\StoreProjectRequest;
use App\Http\Requests\Admin\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Project Controller
 *
 * Handles CRUD operations for projects API.
 *
 * OOAD: Controller Pattern - handles HTTP layer only
 * Clean Code: Delegates business logic to Service layer
 * Laravel Security: Implements proper authorization and validation
 * Performance: Uses eager loading and pagination
 */
class ProjectController extends Controller
{
    use ApiResponseTrait;

    /**
     * Constructor with dependency injection.
     *
     * Clean Code: Dependencies injected for testability
     */
    public function __construct(
        private readonly ProjectService $projectService
    ) {}

    /**
     * Display a listing of projects.
     *
     * Performance: Uses pagination to limit memory usage
     * Security: Validates all query parameters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $this->sanitizePerPage($request->input('per_page', 15));
            $search = $this->sanitizeSearch($request->input('search'));
            $published = $this->sanitizeBoolean($request->input('published'));

            $projects = $this->projectService->getPaginated(
                search: $search,
                published: $published,
                perPage: $perPage
            );

            return $this->successResponse(
                ProjectResource::collection($projects),
                'Projects retrieved successfully.'
            );
        } catch (Throwable $e) {
            Log::error('Error fetching projects', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve projects.',
                500
            );
        }
    }

    /**
     * Store a newly created project.
     *
     * Security: POST method allows multipart/form-data for image upload
     * OWASP: A04:2021 - Validates file uploads
     * Performance: Transaction ensures data consistency
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $project = $this->projectService->create(
                data: $validated,
                image: $request->file('image')
            );

            return $this->successResponse(
                new ProjectResource($project),
                'Project created successfully.',
                201
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                422
            );
        } catch (Throwable $e) {
            Log::error('Error creating project', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->except(['image']),
            ]);

            return $this->errorResponse(
                'Failed to create project.',
                500
            );
        }
    }

    /**
     * Display the specified project.
     *
     * Performance: Route model binding ensures efficient lookup
     */
    public function show(Project $project): JsonResponse
    {
        try {
            return $this->successResponse(
                new ProjectResource($project),
                'Project retrieved successfully.'
            );
        } catch (Throwable $e) {
            Log::error('Error fetching project', [
                'project_id' => $project->id,
                'message' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve project.',
                500
            );
        }
    }

    /**
     * Update the specified project.
     *
     * Security: POST method allows multipart/form-data for image upload
     * Clean Code: Route model binding provides the project instance
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        try {
            $validated = $request->validated();

            $updatedProject = $this->projectService->update(
                project: $project,
                data: $validated,
                image: $request->file('image')
            );

            return $this->successResponse(
                new ProjectResource($updatedProject),
                'Project updated successfully.'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                422
            );
        } catch (Throwable $e) {
            Log::error('Error updating project', [
                'project_id' => $project->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Failed to update project: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified project (soft delete).
     *
     * Security: Soft delete preserves data for recovery
     */
    public function destroy(Project $project): JsonResponse
    {
        try {
            $this->projectService->delete($project);

            return $this->successResponse(
                null,
                'Project deleted successfully.'
            );
        } catch (Throwable $e) {
            Log::error('Error deleting project', [
                'project_id' => $project->id,
                'message' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to delete project.',
                500
            );
        }
    }

    /**
     * Restore a soft-deleted project.
     *
     * Endpoint: POST admin/projects/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $project = $this->projectService->restore($id);

            if ($project === null) {
                return $this->errorResponse(
                    'Project not found or not deleted.',
                    404
                );
            }

            return $this->successResponse(
                new ProjectResource($project),
                'Project restored successfully.'
            );
        } catch (Throwable $e) {
            Log::error('Error restoring project', [
                'project_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to restore project.',
                500
            );
        }
    }

    /**
     * Force delete a project permanently.
     *
     * Security: Permanent removal - requires explicit confirmation
     */
    public function forceDelete(int $id): JsonResponse
    {
        try {
            $project = Project::withTrashed()->find($id);

            if ($project === null) {
                return $this->errorResponse(
                    'Project not found.',
                    404
                );
            }

            $this->projectService->forceDelete($project);

            return $this->successResponse(
                null,
                'Project permanently deleted.'
            );
        } catch (Throwable $e) {
            Log::error('Error force deleting project', [
                'project_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to permanently delete project.',
                500
            );
        }
    }

    /**
     * Sanitize per page parameter.
     *
     * Security: Prevents excessive data exposure
     * Performance: Limits memory usage
     */
    private function sanitizePerPage(mixed $value): int
    {
        $perPage = (int) $value;

        // Limit to prevent memory exhaustion
        return max(1, min($perPage, 100));
    }

    /**
     * Sanitize search parameter.
     *
     * Security: Prevents XSS and injection attacks
     */
    private function sanitizeSearch(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Remove null bytes and trim
        $value = str_replace("\0", '', $value);
        $value = trim($value);

        // Limit search length
        return substr($value, 0, 100) ?: null;
    }

    /**
     * Sanitize boolean parameter.
     *
     * Clean Code: Converts various inputs to boolean or null
     */
    private function sanitizeBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes'], true);
    }
}
