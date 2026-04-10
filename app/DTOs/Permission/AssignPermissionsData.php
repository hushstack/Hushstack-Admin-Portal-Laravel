<?php

namespace App\DTOs\Permission;

use App\Http\Requests\Permission\AssignPermissionRequest;

/**
 * Assign Permissions Data Transfer Object
 *
 * OOAD: DTO Pattern for permission assignment
 * Clean Code: Type-safe array wrapper
 */
readonly class AssignPermissionsData
{
    /**
     * @param array<int> $permissionIds Array of permission IDs to assign
     */
    public function __construct(
        public array $permissionIds,
    ) {
        // Note: In readonly class, we cannot modify properties after construction.
        // Validation should happen in the service layer.
    }

    /**
     * Get sanitized permission IDs.
     *
     * @return array<int>
     */
    public function getSanitizedIds(): array
    {
        $processedIds = array_map('intval', $this->permissionIds);
        $processedIds = array_unique($processedIds);
        return array_values($processedIds);
    }

    /**
     * Create DTO from validated request.
     */
    public static function fromRequest(AssignPermissionRequest $request): self
    {
        return new self(
            permissionIds: $request->validated('permission_ids', []),
        );
    }

    /**
     * Get count of permissions.
     */
    public function count(): int
    {
        return count($this->permissionIds);
    }

    /**
     * Check if empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->permissionIds);
    }
}
