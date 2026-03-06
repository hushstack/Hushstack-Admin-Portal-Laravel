<?php

namespace App\Http\Requests\Admin\UserActivity;

use Illuminate\Foundation\Http\FormRequest;

class IndexActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
