<?php

namespace App\Http\Requests\Collection;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Collection Request
 *
 * OWASP A03:2021 - Injection Prevention
 * - Input validation via FormRequest
 * - Strict type checking
 * - Sanitization of name/slug fields
 *
 * Security:
 * - user_id is auto-set from auth, never from request
 * - Slug auto-generated from name to prevent injection
 */
class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_]+$/', // Alphanumeric, spaces, hyphens, underscores only
            ],
            'description' => [
                'nullable',
                'string',
                'max:65535',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Collection name is required.',
            'name.regex' => 'Collection name can only contain letters, numbers, spaces, hyphens, and underscores.',
            'name.max' => 'Collection name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 65535 characters.',
        ];
    }

    /**
     * Prepare data for validation
     * Sanitize inputs to prevent XSS
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => strip_tags(trim($this->name)),
            ]);
        }

        if ($this->has('description')) {
            $this->merge([
                'description' => strip_tags(trim($this->description)),
            ]);
        }
    }
}
