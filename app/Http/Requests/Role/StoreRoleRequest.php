<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:80'],
            'slug' => ['nullable','string','max:80','alpha_dash','unique:roles,slug'],
            'description' => ['nullable','string','max:255'],
        ];
    }
}