<?php

namespace App\Http\Requests\Product;

use App\Models\Role;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:190', 'alpha_dash', 'unique:products,slug'],
            'sku' => ['required', 'string', 'max:120', 'unique:products,sku'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'price' => ['required', 'numeric', 'min:0'],
            'qty' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'integer', $this->catalogOwnerExistsRule('categories')],
            'brand_id' => ['nullable', 'integer', $this->catalogOwnerExistsRule('brands')],
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
