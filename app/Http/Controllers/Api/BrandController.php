<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Models\Role;
use App\Services\UploadService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly UploadService $uploadService)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);

        $brands = Brand::query()
            ->when($this->isPartner($request) || $this->isUser($request), function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->with('user')
            ->orderBy('name')
            ->paginate($perPage);

        return $this->successResponse(BrandResource::collection($brands), 'Brands loaded.');
    }

    public function show(Brand $brand)
    {
        $brand->load('user');

        if (($this->isPartner(request()) || $this->isUser(request())) &&
            $brand->user_id !== request()->user()?->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        return $this->successResponse(new BrandResource($brand), 'Brand detail loaded.');
    }

    public function store(StoreBrandRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['user_id'] = $request->user()?->id;

        if (Brand::where('slug', $data['slug'])->exists()) {
            return $this->errorResponse('Brand slug already exists.', 422);
        }

        if ($this->isUser($request) && $this->ownsTooMany($request, Brand::class)) {
            return $this->errorResponse('Limit reached: you can create up to 5 brands.', 403);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadService->uploadAndReplace(
                $request->file('image'),
                null,
                'brands/images'
            );
        }

        $brand = Brand::create($data);

        return $this->successResponse(new BrandResource($brand), 'Brand created.', 201);
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        if ($this->isPartner($request) && $brand->user_id !== $request->user()->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        $data = $request->validated();

        if (!array_key_exists('slug', $data) && array_key_exists('name', $data)) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (array_key_exists('slug', $data)) {
            $exists = Brand::where('slug', $data['slug'])
                ->where('id', '!=', $brand->id)
                ->exists();
            if ($exists) {
                return $this->errorResponse('Brand slug already exists.', 422);
            }
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadService->uploadAndReplace(
                $request->file('image'),
                $brand->image,
                'brands/images'
            );
        }

        $brand->update($data);

        return $this->successResponse(new BrandResource($brand), 'Brand updated.');
    }

    public function destroy(Brand $brand)
    {
        $user = request()->user();
        if ($this->isPartner(request()) && $brand->user_id !== $user?->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        $brand->delete();

        return $this->successResponse(null, 'Brand deleted.');
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
