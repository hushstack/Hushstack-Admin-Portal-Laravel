<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:80'],
            'slug' => ['sometimes', 'string', 'max:80', 'alpha_dash', Rule::unique('roles', 'slug')->ignore($roleId)],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
