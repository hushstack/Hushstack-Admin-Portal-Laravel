<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:180'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:190',
                'alpha_dash',
                Rule::unique('products', 'slug')->ignore($product),
            ],
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:120',
                Rule::unique('products', 'sku')->ignore($product),
            ],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'qty' => ['sometimes', 'required', 'integer', 'min:0'],
            'category_id' => ['sometimes', 'required', 'integer', Rule::exists('categories', 'id')],
            'brand_id' => ['sometimes', 'nullable', 'integer', Rule::exists('brands', 'id')],
            'is_stock' => ['sometimes', 'boolean'],
        ];
    }
}
