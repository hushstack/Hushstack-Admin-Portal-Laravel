<?php

namespace App\Http\Requests\Admin\Position;

use Illuminate\Foundation\Http\FormRequest;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:positions,slug'],
            'image' => ['nullable', 'image', 'max:5120'],
            'description' => ['nullable', 'string'],
        ];
    }
}
