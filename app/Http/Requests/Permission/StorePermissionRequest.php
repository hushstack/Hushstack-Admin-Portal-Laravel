<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Permission Request
 * 
 * OWASP A03:2021 - Injection: Strict validation prevents SQL/NoSQL injection.
 * OWASP A03:2021 - Input validation with strict rules.
 * Security: Alpha_dash prevents special characters in slug.
 */
class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by Super Admin middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:80',
                // OWASP: Prevent script injection
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:80',
                // OWASP: Alpha dash prevents special characters
                'alpha_dash',
                'unique:permissions,slug',
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

    /**
     * Prepare data for validation.
     * Security: Sanitize input before validation.
     */
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
