<?php

namespace App\Http\Requests\Admin\GitHub;

use Illuminate\Foundation\Http\FormRequest;

class IndexGitHubCommitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'repo_id' => ['sometimes', 'integer', 'min:1'],
            'repo' => ['sometimes', 'string', 'max:220', 'regex:/^([A-Za-z0-9_.-]+\/)?[A-Za-z0-9_.-]+$/'],
            'branch_name' => ['sometimes', 'string', 'max:255'],
            'search' => ['sometimes', 'string', 'max:200'],
            'author_username' => ['sometimes', 'string', 'max:255'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $repo = $this->input('repo');

        if (is_string($repo)) {
            $this->merge(['repo' => trim($repo)]);
        }

        $branchName = $this->input('branch_name');

        if (is_string($branchName)) {
            $this->merge(['branch_name' => trim($branchName)]);
        }
    }
}
