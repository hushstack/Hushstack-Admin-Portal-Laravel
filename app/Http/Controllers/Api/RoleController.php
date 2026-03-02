<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        $roles = Role::query()
            ->orderBy('id')
            ->get(['id','name','slug','description','created_at','updated_at']);

        return $this->successResponse(RoleResource::collection($roles), 'Roles loaded.');
    }

    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        if (Role::where('slug', $data['slug'])->exists()) {
            return $this->errorResponse('Role slug already exists.', 422);
        }

        $role = Role::create($data);

        return $this->successResponse(new RoleResource($role), 'Role created.', 201);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $data = $request->validated();

        if (array_key_exists('name', $data) && !array_key_exists('slug', $data)) {
            $data['slug'] = Str::slug($data['name']);
        }
        if (array_key_exists('slug', $data)) {
            $exists = Role::where('slug', $data['slug'])
                ->where('id', '!=', $role->id)
                ->exists();
            if ($exists) {
                return $this->errorResponse('Role slug already exists.', 422);
            }
        }

        $role->update($data);

        return $this->successResponse(new RoleResource($role), 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return $this->errorResponse('Role has users assigned. Remove users before deleting.', 422);
        }

        $role->delete();

        return $this->successResponse(null, 'Role deleted.');
    }
}
