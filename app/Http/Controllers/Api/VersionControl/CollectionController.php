<?php

namespace App\Http\Controllers\Api\VersionControl;

use App\Contracts\Services\CollectionServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Collection\StoreCollectionRequest;
use App\Http\Requests\Collection\UpdateCollectionRequest;
use App\Http\Resources\CollectionResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

/**
 * Collection Controller
 *
 * Manages version control collections (projects/repositories)
 *
 * OOAD Patterns:
 * - Single Responsibility: Handles Collection CRUD operations
 * - Security-First: User-scoped queries prevent IDOR/BOLA
 *
 * OWASP Compliance:
 * - A01:2021 - Broken Access Control: User-scoped queries
 * - A03:2021 - Injection: Input validation via FormRequest
 * - A05:2021 - Security Misconfiguration: Permission middleware
 */
class CollectionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CollectionServiceInterface $collectionService,
    ) {}

    /**
     * List all collections
     *
     * Endpoint: GET /api/collections
     * Permission: collections.view
     *
     * Security: Users only see their own collections
     * Performance: Eager load branch count for efficiency
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $userId = $request->user()->id;

        $collections = $this->collectionService->getAllForUser($userId, $perPage);

        return $this->successResponse(
            CollectionResource::collection($collections),
            'Collections retrieved successfully.'
        );
    }

    /**
     * Show single collection
     *
     * Endpoint: GET /api/collections/{collection}
     * Permission: collections.view
     *
     * Security: IDOR/BOLA protection via user-scoped lookup
     */
    public function show(Request $request, int $id)
    {
        $collection = $this->collectionService->getWithBranches($id, $request->user()->id);

        if (! $collection) {
            return $this->errorResponse('Collection not found.', 404);
        }

        return $this->successResponse(
            new CollectionResource($collection),
            'Collection retrieved successfully.'
        );
    }

    /**
     * Create new collection
     *
     * Endpoint: POST /api/collections
     * Permission: collections.create
     *
     * OWASP A03:2021 - Input validation via FormRequest
     */
    public function store(StoreCollectionRequest $request)
    {
        $userId = $request->user()->id;
        $data = $request->validated();

        // Security: Check for unique slug within user's collections
        $slug = $data['slug'] ?? \Illuminate\Support\Str::slug($data['name']);
        if ($this->collectionService->slugExistsForUser($slug, $userId)) {
            return $this->errorResponse('A collection with this name already exists.', 422);
        }

        $collection = $this->collectionService->create($data, $userId);

        return $this->successResponse(
            new CollectionResource($collection),
            'Collection created successfully.',
            201
        );
    }

    /**
     * Update collection
     *
     * Endpoint: PUT /api/collections/{collection}
     * Permission: collections.edit
     *
     * Security: User-scoped lookup prevents unauthorized updates
     */
    public function update(UpdateCollectionRequest $request, int $id)
    {
        $userId = $request->user()->id;
        $data = $request->validated();

        // Security: Check slug uniqueness if being updated
        if (isset($data['slug'])) {
            if ($this->collectionService->slugExistsForUser($data['slug'], $userId, $id)) {
                return $this->errorResponse('This slug is already in use.', 422);
            }
        }

        $collection = $this->collectionService->update($id, $data, $userId);

        if (! $collection) {
            return $this->errorResponse('Collection not found.', 404);
        }

        return $this->successResponse(
            new CollectionResource($collection),
            'Collection updated successfully.'
        );
    }

    /**
     * Delete collection
     *
     * Endpoint: DELETE /api/collections/{collection}
     * Permission: collections.delete
     *
     * Security: User-scoped lookup prevents unauthorized deletion
     */
    public function destroy(Request $request, int $id)
    {
        $deleted = $this->collectionService->delete($id, $request->user()->id);

        if (! $deleted) {
            return $this->errorResponse('Collection not found.', 404);
        }

        return $this->successResponse(
            null,
            'Collection deleted successfully.'
        );
    }

    /**
     * Count collections with summary
     *
     * Endpoint: GET /api/collections/count
     * Permission: collections.view
     *
     * Returns: total collections, branches by stage, total commits
     */
    public function count(Request $request)
    {
        $stats = $this->collectionService->getCountStats($request->user()->id);

        return $this->successResponse($stats, 'Collection counts retrieved successfully.');
    }
}
