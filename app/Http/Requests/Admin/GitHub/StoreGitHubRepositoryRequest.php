<?php

namespace App\Http\Requests\Admin\GitHub;

use Illuminate\Foundation\Http\FormRequest;

class StoreGitHubRepositoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:220', 'regex:/^([A-Za-z0-9_.-]+\/)?[A-Za-z0-9_.-]+$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $fullName = $this->input('full_name') ?: $this->input('repo') ?: $this->input('url');

        if (is_string($fullName)) {
            $fullName = preg_replace('#^https://github\.com/#i', '', trim($fullName));
            $fullName = trim((string) $fullName, " \t\n\r\0\x0B/");
        }

        $this->merge(['full_name' => $fullName]);
    }
}
