<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:160',
                'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($category),
            ],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'department_id' => ['sometimes', 'required', 'integer', Rule::exists('departments', 'id')],
        ];
    }
}
