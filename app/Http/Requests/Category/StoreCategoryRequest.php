<?php

namespace App\Http\Requests\Category;

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
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
        ];
    }
}
