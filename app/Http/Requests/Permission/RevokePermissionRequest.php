<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Revoke Permission Request
 *
 * Validates permission IDs to revoke from a role.
 */
class RevokePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['required', 'integer', 'exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'permission_ids.required' => 'Permission IDs are required.',
            'permission_ids.array' => 'Permission IDs must be an array.',
            'permission_ids.min' => 'At least one permission ID is required.',
            'permission_ids.*.integer' => 'Each permission ID must be an integer.',
            'permission_ids.*.exists' => 'One or more permissions do not exist.',
        ];
    }
}
