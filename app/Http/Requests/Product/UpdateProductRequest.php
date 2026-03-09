<?php

namespace App\Http\Requests\Product;

use App\Models\Role;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

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
            'category_id' => ['sometimes', 'required', 'integer', $this->catalogOwnerExistsRule('categories')],
            'brand_id' => ['sometimes', 'nullable', 'integer', $this->catalogOwnerExistsRule('brands')],
            'is_stock' => ['sometimes', 'boolean'],
        ];
    }

    private function catalogOwnerExistsRule(string $table)
    {
        $rule = Rule::exists($table, 'id');
        $user = $this->user();

        if ($user && in_array($user->role?->slug, [Role::PARTNER_SLUG, Role::USER_SLUG], true)) {
            $rule = $rule->where(fn (Builder $query) => $query->where('user_id', $user->id));
        }

        return $rule;
    }
}
