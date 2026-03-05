<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class SearchUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin middleware protects route; allow here.
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
