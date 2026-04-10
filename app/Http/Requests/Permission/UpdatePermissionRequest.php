<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Permission Request
 * 
 * OWASP A03:2021 - Injection: Strict validation with unique constraint handling.
 * Security: Prevents mass assignment vulnerabilities through explicit rules.
 */
class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by Super Admin middleware
        return true;
    }

    public function rules(): array
    {
        $permissionId = $this->route('permission')?->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
            ],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                'alpha_dash',
                // OWASP: Ensure uniqueness excluding current record
                Rule::unique('permissions', 'slug')->ignore($permissionId),
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The name may only contain letters, numbers, spaces, hyphens, and underscores.',
            'slug.alpha_dash' => 'The slug may only contain letters, numbers, dashes, and underscores.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => strip_tags($this->input('name')),
            ]);
        }
        if ($this->has('description')) {
            $this->merge([
                'description' => strip_tags($this->input('description')),
            ]);
        }
    }
}
