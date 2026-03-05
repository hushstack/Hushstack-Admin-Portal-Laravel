<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\IndexUserRequest;
use App\Http\Requests\Admin\User\SearchUserRequest;
use App\Http\Resources\Admin\UserDetailResource;
use App\Http\Resources\Admin\UserListResource;
use App\Models\User;
use App\Traits\ApiResponseTrait;

class UserAdminController extends Controller
{
    use ApiResponseTrait;

    public function index(IndexUserRequest $request)
    {
        $data = $request->validated();

        $users = User::query()
            ->with('role')
            ->when(isset($data['role']), function ($query) use ($data) {
                $query->whereHas('role', fn ($q) => $q->where('slug', $data['role']));
            })
            ->latest('id')
            ->paginate($data['per_page'] ?? 20);

        return $this->successResponse([
            'data' => UserListResource::collection($users->items()),
            'page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'page_total' => $users->count(),
            'total' => $users->total(),
            'last_page' => $users->lastPage(),
        ], 'Users loaded.');
    }

    public function search(SearchUserRequest $request)
    {
        $data = $request->validated();
        $term = $data['q'];

        $users = User::query()
            ->with('role')
            ->where(function ($query) use ($term) {
                $query->where('username', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->latest('id')
            ->paginate($data['per_page'] ?? 20);

        return $this->successResponse([
            'data' => UserListResource::collection($users->items()),
            'page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'page_total' => $users->count(),
            'total' => $users->total(),
            'last_page' => $users->lastPage(),
        ], 'Users loaded.');
    }

    public function show(User $user)
    {
        $user->load(['role', 'nationality']);

        return $this->successResponse(new UserDetailResource($user), 'User detail loaded.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return $this->successResponse(null, 'User deleted.');
    }
}
