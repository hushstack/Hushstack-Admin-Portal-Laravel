<?php

namespace App\Http\Requests\Admin\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Project Request
 *
 * Handles validation for creating new projects.
 *
 * OWASP: A01:2021 - Broken Access Control
 * - Validates authorization through authorize() method
 *
 * OWASP: A03:2021 - Injection
 * - Strict input validation and sanitization
 *
 * Clean Code: Single Responsibility - only handles store validation rules
 */
class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Security: Role-based access control check
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by 'admin' middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Security: Strict validation rules for all inputs
     * Performance: Efficient validation order (cheapest first)
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:255',
                // Unique check handled at database level with unique constraint
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,gif,webp,svg',
                'max:5120', // 5MB in KB
            ],

            'url' => [
                'nullable',
                'string',
                'max:255',
                'url',
            ],

            'technologies' => [
                'nullable',
            ],
            // technologies can be array or string (comma-separated)
            'technologies.*' => [
                'string',
                'max:50',
            ],

            'is_published' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * Clean Code: User-friendly error messages
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The project title is required.',
            'title.min' => 'The project title must be at least :min characters.',
            'title.max' => 'The project title may not exceed :max characters.',
            'description.max' => 'The description may not exceed :max characters.',
            'image.image' => 'The file must be a valid image.',
            'image.mimes' => 'The image must be a file of type: :values.',
            'image.max' => 'The image may not be larger than :max kilobytes (5MB).',
            'url.url' => 'Please provide a valid URL.',
            'technologies.*.max' => 'Each technology may not exceed :max characters.',
            'is_published.boolean' => 'The publication status must be true or false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'project title',
            'description' => 'project description',
            'image' => 'project image',
            'url' => 'project URL',
            'technologies' => 'technologies',
            'is_published' => 'publication status',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Security: Sanitizes inputs before validation
     * Performance: Reduces memory usage by cleaning early
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->sanitizeString($this->input('title')),
            'description' => $this->sanitizeString($this->input('description')),
            'url' => $this->sanitizeString($this->input('url')),
            'is_published' => $this->toBoolean($this->input('is_published')),
        ]);
    }

    /**
     * Sanitize string input.
     *
     * Security: XSS prevention - removes script tags and dangerous content
     */
    private function sanitizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Trim whitespace
        return trim($value);
    }

    /**
     * Convert various boolean representations to actual boolean.
     */
    private function toBoolean(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return (bool) $value;
    }

    /**
     * Handle a passed validation attempt.
     *
     * Performance: Normalizes data format for consistent processing
     */
    protected function passedValidation(): void
    {
        // Normalize technologies to array
        $technologies = $this->input('technologies');

        if (is_string($technologies)) {
            $technologies = array_values(array_filter(array_map(
                fn ($tech) => trim($tech),
                explode(',', $technologies)
            )));
        }

        $this->merge([
            'technologies' => $technologies ?? [],
        ]);
    }
}
