<?php

namespace App\Http\Requests\Category;

use App\Models\Role;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160', 'alpha_dash', 'unique:categories,slug'],
            'image' => ['nullable', 'image', 'max:5120'],
            'department_id' => ['required', 'integer', $this->departmentExistsRule()],
        ];
    }

    private function departmentExistsRule()
    {
        $rule = Rule::exists('departments', 'id');
        $user = $this->user();

        if ($user && in_array($user->role?->slug, [Role::PARTNER_SLUG, Role::USER_SLUG], true)) {
            $rule = $rule->where(fn (Builder $query) => $query->where('user_id', $user->id));
        }

        return $rule;
    }
}
