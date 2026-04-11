<?php

namespace App\Http\Requests\Commit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Commit Request
 *
 * OWASP A03:2021 - Injection Prevention
 * - Input validation via FormRequest
 * - SHA format validation for integrity
 * - Branch ownership verification
 *
 * Security:
 * - user_id is auto-set from auth
 * - Branch must belong to authenticated user
 * - SHA format validation prevents injection
 */
class StoreCommitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
                // Security: Verify branch belongs to authenticated user
                Rule::exists('branches', 'id')
                    ->where('user_id', auth()->id()),
            ],
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:65535',
            ],
            'sha' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[a-f0-9]+$/i', // SHA format validation
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'A branch is required.',
            'branch_id.exists' => 'The selected branch does not exist or does not belong to you.',
            'name.required' => 'Commit name/message is required.',
            'sha.regex' => 'SHA must be a valid hexadecimal string.',
            'sha.max' => 'SHA cannot exceed 64 characters.',
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

        if ($this->has('sha')) {
            $this->merge([
                'sha' => strtolower(preg_replace('/[^a-f0-9]/', '', $this->sha)),
            ]);
        }
    }
}
