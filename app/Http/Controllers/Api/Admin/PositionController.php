<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Position\StorePositionRequest;
use App\Http\Requests\Admin\Position\UpdatePositionRequest;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use App\Services\PositionService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly PositionService $positionService) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $positions = $this->positionService->getAll($perPage);

        return $this->successResponse(PositionResource::collection($positions), 'Positions loaded.');
    }

    public function show(Position $position)
    {
        return $this->successResponse(new PositionResource($position), 'Position detail loaded.');
    }

    public function store(StorePositionRequest $request)
    {
        $position = $this->positionService->create(
            $request->validated(),
            $request->file('image')
        );

        return $this->successResponse(new PositionResource($position), 'Position created.', 201);
    }

    public function update(UpdatePositionRequest $request, Position $position)
    {
        $position = $this->positionService->update(
            $position,
            $request->validated(),
            $request->file('image')
        );

        return $this->successResponse(new PositionResource($position), 'Position updated.');
    }

    public function destroy(Position $position)
    {
        $this->positionService->delete($position);

        return $this->successResponse(null, 'Position deleted.');
    }
}
