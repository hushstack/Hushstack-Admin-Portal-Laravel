<?php

namespace App\Http\Requests\Admin\Position;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:positions,slug,'.$this->position?->id],
            'image' => ['nullable', 'image', 'max:5120'],
            'description' => ['nullable', 'string'],
        ];
    }
}
