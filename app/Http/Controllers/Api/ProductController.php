<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Role;
use App\Services\UploadService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly UploadService $uploadService) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $categoryId = $request->integer('category_id');
        $departmentId = $request->integer('department_id');
        $brandId = $request->integer('brand_id');
        $isStock = $request->has('is_stock') ? $request->boolean('is_stock') : null;

        $products = Product::query()
            ->with(['category', 'category.department', 'brand', 'user'])
            ->when($this->isPartner($request) || $this->isUser($request), function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($departmentId, function ($query) use ($departmentId) {
                $query->whereHas('category', fn ($q) => $q->where('department_id', $departmentId));
            })
            ->when($brandId, fn ($query) => $query->where('brand_id', $brandId))
            ->when(! is_null($isStock), fn ($query) => $query->where('is_stock', $isStock))
            ->orderBy('name')
            ->paginate($perPage);

        return $this->successResponse(ProductResource::collection($products), 'Products loaded.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'category.department', 'brand', 'user']);

        if (($this->isPartner(request()) || $this->isUser(request())) &&
            $product->user_id !== request()->user()?->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        return $this->successResponse(new ProductResource($product), 'Product detail loaded.');
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['user_id'] = $request->user()?->id;

        if (Product::where('slug', $data['slug'])->exists()) {
            return $this->errorResponse('Product slug already exists.', 422);
        }
        if (Product::where('sku', $data['sku'])->exists()) {
            return $this->errorResponse('Product SKU already exists.', 422);
        }

        if ($this->isUser($request) && $this->ownsTooMany($request, Product::class)) {
            return $this->errorResponse('Limit reached: you can create up to 5 products.', 403);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadService->uploadAndReplace(
                $request->file('image'),
                null,
                'products/images'
            );
        }

        $product = Product::create($data);
        $product->load(['category', 'brand']);

        return $this->successResponse(new ProductResource($product), 'Product created.', 201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        // Fix IDOR/BOLA: Ensure normal users also pass ownership checks
        if (($this->isPartner($request) || $this->isUser($request)) && $product->user_id !== $request->user()->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        $data = $request->validated();

        if (! array_key_exists('slug', $data) && array_key_exists('name', $data)) {
            $data['slug'] = Str::slug($data['name']);
        }

        if (array_key_exists('slug', $data)) {
            $existsSlug = Product::where('slug', $data['slug'])
                ->where('id', '!=', $product->id)
                ->exists();
            if ($existsSlug) {
                return $this->errorResponse('Product slug already exists.', 422);
            }
        }

        if (array_key_exists('sku', $data)) {
            $existsSku = Product::where('sku', $data['sku'])
                ->where('id', '!=', $product->id)
                ->exists();
            if ($existsSku) {
                return $this->errorResponse('Product SKU already exists.', 422);
            }
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadService->uploadAndReplace(
                $request->file('image'),
                $product->image,
                'products/images'
            );
        }

        $product->update($data);
        $product->load(['category', 'brand']);

        return $this->successResponse(new ProductResource($product), 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $user = request()->user();
        // Fix IDOR/BOLA: Ensure normal users also pass ownership checks
        if (($this->isPartner(request()) || $this->isUser(request())) && $product->user_id !== $user?->id) {
            return $this->errorResponse('Forbidden.', 403);
        }

        $product->delete();

        return $this->successResponse(null, 'Product deleted.');
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
