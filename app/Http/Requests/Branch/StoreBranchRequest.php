<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Branch Request
 *
 * OWASP A03:2021 - Injection Prevention
 * - Input validation via FormRequest
 * - Enum validation for status and stage
 * - Collection ownership verification
 *
 * Security:
 * - user_id is auto-set from auth
 * - Collection must belong to authenticated user
 */
class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'collection_id' => [
                'required',
                'integer',
                'exists:collections,id',
                // Security: Verify collection belongs to authenticated user
                Rule::exists('collections', 'id')
                    ->where('user_id', auth()->id()),
            ],
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_\.\/]+$/', // Allow branch naming conventions
            ],
            'description' => [
                'nullable',
                'string',
                'max:65535',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(Branch::STATUSES),
            ],
            'stage' => [
                'sometimes',
                'string',
                Rule::in(Branch::STAGES),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'collection_id.required' => 'A collection is required.',
            'collection_id.exists' => 'The selected collection does not exist or does not belong to you.',
            'name.required' => 'Branch name is required.',
            'name.regex' => 'Branch name can only contain letters, numbers, spaces, hyphens, underscores, dots, and forward slashes.',
            'status.in' => 'Invalid status. Must be one of: ' . implode(', ', Branch::STATUSES),
            'stage.in' => 'Invalid stage. Must be one of: ' . implode(', ', Branch::STAGES),
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
