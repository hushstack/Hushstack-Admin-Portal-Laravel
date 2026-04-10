<?php

namespace App\DTOs\Permission;

use App\Http\Requests\Permission\StorePermissionRequest;

/**
 * Create Permission Data Transfer Object
 *
 * OOAD: DTO Pattern - encapsulates data for layer transfer
 * Clean Code: Immutable data object with validation
 */
readonly class CreatePermissionData
{
    /**
     * @param string $name Permission display name
     * @param string|null $slug Optional unique identifier (auto-generated if null)
     * @param string|null $description Optional description
     */
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?string $description = null,
    ) {}

    /**
     * Create DTO from validated request.
     */
    public static function fromRequest(StorePermissionRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
        );
    }

    /**
     * Convert to array for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
        ];
    }
}
