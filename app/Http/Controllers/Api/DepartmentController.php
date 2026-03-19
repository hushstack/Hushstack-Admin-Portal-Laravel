<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Models\Role;
use App\Services\UploadService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly UploadService $uploadService)
    {
    }

    public function index(Request $request)
    {
        $withCategories = $request->boolean('with_categories');
        $perPage = $request->integer('per_page', 15);

        $departments = Department::query()
            ->when($this->isPartner($request) || $this->isUser($request), function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->when($withCategories, fn ($query) => $query->with(['categories.department']))
            ->with('user')
            ->orderBy('name')
            ->paginate($perPage);

        return $this->successResponse(DepartmentResource::collection($departments), 'Departments loaded.');
    }

    public function show(Department $department)
    {
        $department->load(['categories', 'user']);

        if (($this->isPartner(request()) || $this->isUser(request())) &&
            $department->user_id !== request()->user()?->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        return $this->successResponse(new DepartmentResource($department), 'Department detail loaded.');
    }

    public function store(StoreDepartmentRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['user_id'] = $request->user()?->id;

        if (Department::where('slug', $data['slug'])->exists()) {
            return $this->errorResponse('Department slug already exists.', 422);
        }

        if ($this->isUser($request) && $this->ownsTooMany($request, Department::class)) {
            return $this->errorResponse('Limit reached: you can create up to 5 departments.', 403);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadService->uploadAndReplace(
                $request->file('image'),
                null,
                'departments/images'
            );
        }

        $department = Department::create($data);

        return $this->successResponse(new DepartmentResource($department), 'Department created.', 201);
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        // Fix IDOR/BOLA: Ensure normal users also pass ownership checks
        if (($this->isPartner($request) || $this->isUser($request)) && $department->user_id !== $request->user()->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        $data = $request->validated();

        if (!array_key_exists('slug', $data) && array_key_exists('name', $data)) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (array_key_exists('slug', $data)) {
            $exists = Department::where('slug', $data['slug'])
                ->where('id', '!=', $department->id)
                ->exists();
            if ($exists) {
                return $this->errorResponse('Department slug already exists.', 422);
            }
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadService->uploadAndReplace(
                $request->file('image'),
                $department->image,
                'departments/images'
            );
        }

        $department->update($data);

        return $this->successResponse(new DepartmentResource($department), 'Department updated.');
    }

    public function destroy(Department $department)
    {
        $user = request()->user();
        // Fix IDOR/BOLA: Ensure normal users also pass ownership checks
        if (($this->isPartner(request()) || $this->isUser(request())) && $department->user_id !== $user?->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        $department->delete();

        return $this->successResponse(null, 'Department deleted.');
    }

    private function isPartner(Request $request): bool
    {
        return $request->user()?->role?->slug === Role::PARTNER_SLUG;
    }

    private function isUser(Request $request): bool
    {
        return $request->user()?->role?->slug === Role::USER_SLUG;
    }

    private function ownsTooMany(Request $request, string $modelClass): bool
    {
        return $modelClass::where('user_id', $request->user()->id)->count() >= 5;
    }
}
