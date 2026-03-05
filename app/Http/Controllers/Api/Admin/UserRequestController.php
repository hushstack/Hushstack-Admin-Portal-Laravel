<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest\IndexUserRequest;
use App\Http\Resources\Admin\UserRequestResource;
use App\Models\UserRequest;
use App\Traits\ApiResponseTrait;

class UserRequestController extends Controller
{
    use ApiResponseTrait;

    public function index(IndexUserRequest $request)
    {
        $data = $request->validated();

        $requests = UserRequest::query()
            ->latest('id')
            ->paginate($data['per_page'] ?? 20);

        return $this->successResponse([
            'data' => UserRequestResource::collection($requests->items()),
            'page' => $requests->currentPage(),
            'per_page' => $requests->perPage(),
            'page_total' => $requests->count(),
            'total' => $requests->total(),
            'last_page' => $requests->lastPage(),
        ], 'User requests loaded.');
    }
}
