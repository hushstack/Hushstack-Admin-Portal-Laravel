<?php

namespace App\Http\Requests\Commit;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Commit Request
 *
 * OWASP A03:2021 - Injection Prevention
 * - Input validation via FormRequest
 * - SHA format validation for integrity
 */
class UpdateCommitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
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
                'regex:/^[a-f0-9]+$/i',
            ],
        ];
    }

    public function messages(): array
    {
        return [
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
