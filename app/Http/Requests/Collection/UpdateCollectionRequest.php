<?php

namespace App\Http\Requests\Collection;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Collection Request
 *
 * OWASP A03:2021 - Injection Prevention
 * - Input validation via FormRequest
 * - Strict type checking
 * - Slug uniqueness check across other records
 *
 * Security:
 * - user_id is never updatable
 * - Slug regenerated if name changes
 */
class UpdateCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $collectionId = $this->route('collection')?->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:1',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
            ],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('collections', 'slug')
                    ->ignore($collectionId)
                    ->where('user_id', auth()->id()),
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
            'slug.unique' => 'This slug is already in use for another collection.',
        ];
    }

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
