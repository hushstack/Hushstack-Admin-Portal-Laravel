<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:160',
                'alpha_dash',
                Rule::unique('brands', 'slug')->ignore($this->route('brand')),
            ],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
        ];
    }
}
