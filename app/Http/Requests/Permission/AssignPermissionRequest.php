<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Assign Permission Request
 * 
 * OWASP A03:2021 - Strict validation prevents array injection attacks.
 * Security: Validates array content and prevents empty assignments.
 */
class AssignPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by Super Admin middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_ids' => [
                'required',
                'array',
                'min:1',
                // OWASP: Prevent excessive array size (DoS protection)
                'max:100',
            ],
            'permission_ids.*' => [
                'required',
                'integer',
                'min:1',
                // OWASP: Validate permission exists (database level)
                'exists:permissions,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'permission_ids.required' => 'At least one permission must be selected.',
            'permission_ids.array' => 'Permissions must be provided as an array.',
            'permission_ids.min' => 'At least one permission must be selected.',
            'permission_ids.max' => 'Cannot assign more than 100 permissions at once.',
            'permission_ids.*.exists' => 'One or more selected permissions do not exist.',
        ];
    }
}
