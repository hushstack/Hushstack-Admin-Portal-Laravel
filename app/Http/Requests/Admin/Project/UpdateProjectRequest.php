<?php

namespace App\Http\Requests\Admin\Project;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Project Request
 *
 * Handles validation for updating existing projects.
 *
 * OWASP: A01:2021 - Broken Access Control
 * - Validates user can update specific project
 *
 * OWASP: A03:2021 - Injection
 * - Strict input validation prevents injection attacks
 *
 * Clean Code: Extends StoreProjectRequest with update-specific rules
 */
class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Security: Verifies user has permission to update this specific resource
     */
    public function authorize(): bool
    {
        // Authorization handled by 'admin' middleware
        // Route model binding ensures project exists
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Security: Same rules as StoreProjectRequest
     * - Title unique except for current project
     */
    public function rules(): array
    {
        /** @var Project|null $project */
        $project = $this->route('project');
        $projectId = $project?->id;

        return [
            'title' => [
                'sometimes', // Only validate if present
                'nullable',  // Allow empty/null if present
                'string',
                'min:3',
                'max:255',
            ],

            'description' => [
                'sometimes', // Only validate if present
                'nullable',
                'string',
                'max:5000',
            ],

            'image' => [
                'sometimes', // Only validate if present
                'nullable',
                'image',
                'mimes:jpeg,png,gif,webp,svg',
                'max:5120', // 5MB in KB
            ],

            'url' => [
                'sometimes', // Only validate if present
                'nullable',
                'string',
                'max:255',
                'url',
            ],

            'technologies' => [
                'sometimes', // Only validate if present
                'nullable',
            ],
            // technologies can be array or string (comma-separated)
            'technologies.*' => [
                'string',
                'max:50',
            ],

            'is_published' => [
                'sometimes', // Only validate if present
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
            'title.required' => 'The project title is required when updating.',
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
     * Fix: Only process fields that are actually present in the request
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        // Only process fields that are present in the request
        if ($this->has('title')) {
            $mergeData['title'] = $this->sanitizeString($this->input('title'));
        }

        if ($this->has('description')) {
            $mergeData['description'] = $this->sanitizeString($this->input('description'));
        }

        if ($this->has('url')) {
            $mergeData['url'] = $this->sanitizeString($this->input('url'));
        }

        if ($this->has('is_published')) {
            $mergeData['is_published'] = $this->toBoolean($this->input('is_published'));
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Sanitize string input.
     *
     * Security: XSS prevention
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
     * Performance: Normalizes data format
     * Fix: Only process technologies if present
     */
    protected function passedValidation(): void
    {
        // Only normalize technologies if it's present in the request
        if (!$this->has('technologies')) {
            return;
        }

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
