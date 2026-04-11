<?php

namespace App\Http\Controllers\Api\VersionControl;

use App\Contracts\Services\BranchServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

/**
 * Branch Controller
 *
 * Manages version control branches within collections
 *
 * OOAD Patterns:
 * - Single Responsibility: Handles Branch CRUD operations
 * - Security-First: User-scoped queries prevent IDOR/BOLA
 *
 * OWASP Compliance:
 * - A01:2021 - Broken Access Control: User-scoped queries
 * - A03:2021 - Injection: Input validation via FormRequest
 * - A05:2021 - Security Misconfiguration: Permission middleware
 */
class BranchController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly BranchServiceInterface $branchService,
    ) {}

    /**
     * List all branches
     *
     * Endpoint: GET /api/branches
     * Permission: branches.view
     *
     * Filter: stage (local, dev, staging, pvt, prod)
     * Security: Users only see their own branches
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $stage = $request->input('stage');
        $userId = $request->user()->id;

        $branches = $this->branchService->getAllForUser($userId, $stage, $perPage);

        return $this->successResponse(
            BranchResource::collection($branches),
            'Branches retrieved successfully.'
        );
    }

    /**
     * Show single branch
     *
     * Endpoint: GET /api/branches/{branch}
     * Permission: branches.view
     */
    public function show(Request $request, int $id)
    {
        $branch = $this->branchService->getWithCommits($id, $request->user()->id);

        if (! $branch) {
            return $this->errorResponse('Branch not found.', 404);
        }

        return $this->successResponse(
            new BranchResource($branch),
            'Branch retrieved successfully.'
        );
    }

    /**
     * Create new branch
     *
     * Endpoint: POST /api/branches
     * Permission: branches.create
     */
    public function store(StoreBranchRequest $request)
    {
        $userId = $request->user()->id;
        $data = $request->validated();

        // Verify collection ownership (additional security check)
        if (! $this->branchService->verifyCollectionOwnership($data['collection_id'], $userId)) {
            return $this->errorResponse('Collection not found or access denied.', 404);
        }

        $branch = $this->branchService->create($data, $userId);

        return $this->successResponse(
            new BranchResource($branch),
            'Branch created successfully.',
            201
        );
    }

    /**
     * Update branch
     *
     * Endpoint: PUT /api/branches/{branch}
     * Permission: branches.edit
     */
    public function update(UpdateBranchRequest $request, int $id)
    {
        $branch = $this->branchService->update($id, $request->validated(), $request->user()->id);

        if (! $branch) {
            return $this->errorResponse('Branch not found.', 404);
        }

        return $this->successResponse(
            new BranchResource($branch),
            'Branch updated successfully.'
        );
    }

    /**
     * Delete branch
     *
     * Endpoint: DELETE /api/branches/{branch}
     * Permission: branches.delete
     */
    public function destroy(Request $request, int $id)
    {
        $deleted = $this->branchService->delete($id, $request->user()->id);

        if (! $deleted) {
            return $this->errorResponse('Branch not found.', 404);
        }

        return $this->successResponse(
            null,
            'Branch deleted successfully.'
        );
    }

    /**
     * List branches by collection
     *
     * Endpoint: GET /api/collections/{collection}/branches
     * Permission: branches.view
     *
     * Filter: stage (local, dev, staging, pvt, prod)
     */
    public function byCollection(Request $request, int $collectionId)
    {
        $perPage = $request->integer('per_page', 15);
        $stage = $request->input('stage');
        $userId = $request->user()->id;

        // Security: Verify collection belongs to user
        if (! $this->branchService->verifyCollectionOwnership($collectionId, $userId)) {
            return $this->errorResponse('Collection not found.', 404);
        }

        $branches = $this->branchService->getByCollection($collectionId, $userId, $stage, $perPage);

        return $this->successResponse(
            BranchResource::collection($branches),
            'Branches retrieved successfully.'
        );
    }

    /**
     * Count branches by stage
     *
     * Endpoint: GET /api/branches/count
     * Permission: branches.view
     */
    public function count(Request $request)
    {
        $userId = $request->user()->id;
        $collectionId = $request->input('collection_id');

        // Security: Verify collection belongs to user if provided
        if ($collectionId && ! $this->branchService->verifyCollectionOwnership($collectionId, $userId)) {
            return $this->errorResponse('Collection not found.', 404);
        }

        $stats = $this->branchService->getCountStats($userId, $collectionId);

        return $this->successResponse($stats, 'Branch counts retrieved successfully.');
    }
}
