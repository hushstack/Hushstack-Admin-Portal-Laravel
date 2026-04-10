<?php

namespace App\DTOs\Permission;

use App\Http\Requests\Permission\UpdatePermissionRequest;

/**
 * Update Permission Data Transfer Object
 *
 * OOAD: DTO Pattern - encapsulates partial update data
 * Clean Code: Nullable fields indicate optional updates
 */
readonly class UpdatePermissionData
{
    /**
     * @param string|null $name Optional new name
     * @param string|null $slug Optional new slug
     * @param string|null $description Optional new description
     */
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
    ) {}

    /**
     * Create DTO from validated request.
     */
    public static function fromRequest(UpdatePermissionRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
        );
    }

    /**
     * Check if any data is present for update.
     */
    public function hasData(): bool
    {
        return $this->name !== null
            || $this->slug !== null
            || $this->description !== null;
    }

    /**
     * Convert to array for database storage (only non-null values).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}
