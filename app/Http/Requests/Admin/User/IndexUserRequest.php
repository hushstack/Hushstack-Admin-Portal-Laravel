<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route group already protected by admin middleware, but keep explicit allow.
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'string', 'in:admin,user,member,partner'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
