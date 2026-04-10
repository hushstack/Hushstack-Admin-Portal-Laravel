<?php

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const DEFAULT_SLUG = 'user';

    public const ADMIN_SLUG = 'admin';

    public const PARTNER_SLUG = 'partner';

    public const USER_SLUG = 'user';

    public const SUPER_ADMIN_SLUG = 'super-admin';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Users with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Permissions assigned to this role.
     * Many-to-many relationship with Permission model.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Check if role has a specific permission by slug.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->permissions()
            ->where('slug', $permissionSlug)
            ->exists();
    }

    /**
     * Check if role has a specific permission using enum.
     */
    public function hasPermissionEnum(PermissionEnum $permission): bool
    {
        return $this->hasPermission($permission->value);
    }

    /**
     * Check if role has any of the given permissions.
     *
     * @param array<int, PermissionEnum> $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $slugs = array_map(fn (PermissionEnum $p) => $p->value, $permissions);

        return $this->permissions()
            ->whereIn('slug', $slugs)
            ->exists();
    }

    /**
     * Check if role has all of the given permissions.
     *
     * @param array<int, PermissionEnum> $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $slugs = array_map(fn (PermissionEnum $p) => $p->value, $permissions);
        $count = count($slugs);

        return $this->permissions()
            ->whereIn('slug', $slugs)
            ->count() === $count;
    }

    /**
     * Sync permissions for this role (by IDs).
     *
     * @param array<int> $permissionIds
     */
    public function syncPermissions(array $permissionIds): array
    {
        return $this->permissions()->sync($permissionIds);
    }

    /**
     * Sync permissions for this role (by enum).
     *
     * @param array<int, PermissionEnum> $permissions
     */
    public function syncPermissionEnums(array $permissions): array
    {
        $slugs = array_map(fn (PermissionEnum $p) => $p->value, $permissions);
        $permissionIds = \App\Models\Permission::whereIn('slug', $slugs)
            ->pluck('id')
            ->toArray();

        return $this->syncPermissions($permissionIds);
    }

    /**
     * Get all permission enums for this role.
     *
     * @return array<int, PermissionEnum>
     */
    public function getPermissionEnums(): array
    {
        if ($this->isSuperAdmin()) {
            return PermissionEnum::cases();
        }

        $slugs = $this->permissions()->pluck('slug')->toArray();

        return array_filter(
            array_map(
                fn (string $slug) => PermissionEnum::tryFrom($slug),
                $slugs
            )
        );
    }

    /**
     * Check if this is a super admin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->slug === self::SUPER_ADMIN_SLUG;
    }

    /**
     * Check if this is an admin role (includes super admin).
     */
    public function isAdmin(): bool
    {
        return in_array($this->slug, [self::ADMIN_SLUG, self::SUPER_ADMIN_SLUG], true);
    }

    public static function defaultId(): int
    {
        return (int) (static::query()
            ->where('slug', self::DEFAULT_SLUG)
            ->value('id') ?? 0);
    }

    public static function idBySlug(string $slug): int
    {
        return (int) (static::query()
            ->where('slug', $slug)
            ->value('id') ?? 0);
    }
}
