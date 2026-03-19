<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserActivity\IndexActivityRequest;
use App\Http\Resources\Admin\UserActivityResource;
use App\Models\UserActivity;
use App\Traits\ApiResponseTrait;

class UserActivityController extends Controller
{
    use ApiResponseTrait;

    public function index(IndexActivityRequest $request)
    {
        $data = $request->validated();

        $activities = UserActivity::query()
            ->with(['user', 'role'])
            ->latest('id')
            ->paginate($data['per_page'] ?? 20);

        return $this->successResponse([
            'data' => UserActivityResource::collection($activities),
            'page' => $activities->currentPage(),
            'per_page' => $activities->perPage(),
            'page_total' => $activities->count(),
            'total' => $activities->total(),
            'last_page' => $activities->lastPage(),
        ], 'User activities loaded.');
    }
}
