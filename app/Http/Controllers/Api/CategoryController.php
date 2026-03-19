<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Role;
use App\Services\UploadService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly UploadService $uploadService)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $departmentId = $request->integer('department_id');

        $categories = Category::query()
            ->when($this->isPartner($request) || $this->isUser($request), function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->with(['department', 'department.user', 'user'])
            ->orderBy('name')
            ->paginate($perPage);

        return $this->successResponse(CategoryResource::collection($categories), 'Categories loaded.');
    }

    public function show(Category $category)
    {
        $category->load(['department', 'user']);

        if (($this->isPartner(request()) || $this->isUser(request())) &&
            $category->user_id !== request()->user()?->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        return $this->successResponse(new CategoryResource($category), 'Category detail loaded.');
    }

    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['user_id'] = $request->user()?->id;

        if (Category::where('slug', $data['slug'])->exists()) {
            return $this->errorResponse('Category slug already exists.', 422);
        }

        if ($this->isUser($request) && $this->ownsTooMany($request, Category::class)) {
            return $this->errorResponse('Limit reached: you can create up to 5 categories.', 403);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadService->uploadAndReplace(
                $request->file('image'),
                null,
                'categories/images'
            );
        }

        $category = Category::create($data);

        return $this->successResponse(new CategoryResource($category), 'Category created.', 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        // Fix IDOR/BOLA: Ensure normal users also pass ownership checks
        if (($this->isPartner($request) || $this->isUser($request)) && $category->user_id !== $request->user()->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        $data = $request->validated();

        if (!array_key_exists('slug', $data) && array_key_exists('name', $data)) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (array_key_exists('slug', $data)) {
            $exists = Category::where('slug', $data['slug'])
                ->where('id', '!=', $category->id)
                ->exists();
            if ($exists) {
                return $this->errorResponse('Category slug already exists.', 422);
            }
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadService->uploadAndReplace(
                $request->file('image'),
                $category->image,
                'categories/images'
            );
        }

        $category->update($data);

        return $this->successResponse(new CategoryResource($category), 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $user = request()->user();
        // Fix IDOR/BOLA: Ensure normal users also pass ownership checks
        if (($this->isPartner(request()) || $this->isUser(request())) && $category->user_id !== $user?->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        $category->delete();

        return $this->successResponse(null, 'Category deleted.');
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
