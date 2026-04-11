<?php

namespace App\Http\Controllers\Api\VersionControl;

use App\Contracts\Services\CommitServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commit\StoreCommitRequest;
use App\Http\Requests\Commit\UpdateCommitRequest;
use App\Http\Resources\CommitResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

/**
 * Commit Controller
 *
 * Manages version control commits within branches
 *
 * OOAD Patterns:
 * - Single Responsibility: Handles Commit CRUD operations
 * - Security-First: User-scoped queries prevent IDOR/BOLA
 *
 * OWASP Compliance:
 * - A01:2021 - Broken Access Control: User-scoped queries
 * - A03:2021 - Injection: Input validation via FormRequest
 * - A05:2021 - Security Misconfiguration: Permission middleware
 */
class CommitController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CommitServiceInterface $commitService,
    ) {}

    /**
     * List all commits
     *
     * Endpoint: GET /api/commits
     * Permission: commits.view
     *
     * Filter: branch_id, collection_id
     * Security: Users only see their own commits
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = [
            'branch_id' => $request->input('branch_id'),
            'collection_id' => $request->input('collection_id'),
        ];
        $userId = $request->user()->id;

        // Security: Validate branch_id if provided
        if ($filters['branch_id'] && ! $this->commitService->verifyBranchOwnership($filters['branch_id'], $userId)) {
            return $this->errorResponse('Branch not found.', 404);
        }

        $commits = $this->commitService->getAllForUser($userId, $filters, $perPage);

        return $this->successResponse(
            CommitResource::collection($commits),
            'Commits retrieved successfully.'
        );
    }

    /**
     * Show single commit
     *
     * Endpoint: GET /api/commits/{commit}
     * Permission: commits.view
     */
    public function show(Request $request, int $id)
    {
        $commit = $this->commitService->getById($id, $request->user()->id);

        if (! $commit) {
            return $this->errorResponse('Commit not found.', 404);
        }

        return $this->successResponse(
            new CommitResource($commit),
            'Commit retrieved successfully.'
        );
    }

    /**
     * Create new commit
     *
     * Endpoint: POST /api/commits
     * Permission: commits.create
     */
    public function store(StoreCommitRequest $request)
    {
        $userId = $request->user()->id;
        $data = $request->validated();

        // Security: Verify branch ownership (additional check)
        if (! $this->commitService->verifyBranchOwnership($data['branch_id'], $userId)) {
            return $this->errorResponse('Branch not found or access denied.', 404);
        }

        $commit = $this->commitService->create($data, $userId);

        return $this->successResponse(
            new CommitResource($commit),
            'Commit created successfully.',
            201
        );
    }

    /**
     * Update commit
     *
     * Endpoint: PUT /api/commits/{commit}
     * Permission: commits.edit
     */
    public function update(UpdateCommitRequest $request, int $id)
    {
        $commit = $this->commitService->update($id, $request->validated(), $request->user()->id);

        if (! $commit) {
            return $this->errorResponse('Commit not found.', 404);
        }

        return $this->successResponse(
            new CommitResource($commit),
            'Commit updated successfully.'
        );
    }

    /**
     * Delete commit
     *
     * Endpoint: DELETE /api/commits/{commit}
     * Permission: commits.delete
     */
    public function destroy(Request $request, int $id)
    {
        $deleted = $this->commitService->delete($id, $request->user()->id);

        if (! $deleted) {
            return $this->errorResponse('Commit not found.', 404);
        }

        return $this->successResponse(
            null,
            'Commit deleted successfully.'
        );
    }

    /**
     * List commits by branch
     *
     * Endpoint: GET /api/branches/{branch}/commits
     * Permission: commits.view
     */
    public function byBranch(Request $request, int $branchId)
    {
        $perPage = $request->integer('per_page', 15);
        $userId = $request->user()->id;

        // Security: Verify branch belongs to user
        if (! $this->commitService->verifyBranchOwnership($branchId, $userId)) {
            return $this->errorResponse('Branch not found.', 404);
        }

        $commits = $this->commitService->getByBranch($branchId, $userId, $perPage);

        return $this->successResponse(
            CommitResource::collection($commits),
            'Commits retrieved successfully.'
        );
    }

    /**
     * Count commits
     *
     * Endpoint: GET /api/commits/count
     * Permission: commits.view
     */
    public function count(Request $request)
    {
        $userId = $request->user()->id;
        $branchId = $request->input('branch_id');
        $collectionId = $request->input('collection_id');

        // Security validation for branch_id
        if ($branchId && ! $this->commitService->verifyBranchOwnership($branchId, $userId)) {
            return $this->errorResponse('Branch not found.', 404);
        }

        $stats = $this->commitService->getCountStats($userId, $branchId, $collectionId);

        return $this->successResponse($stats, 'Commit counts retrieved successfully.');
    }
}
