<?php

namespace App\Models;

use App\Enums\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'phone_number',
        'social_login_key',
        'password',
        'is_verified',
        'picture',
        'cover',
        'bio',
        'note',
        'birth_of_date',
        'age',
        'nationality_id',
        'role_id',
        'contact_url',
        'address',
        'provider',
        'provider_id',
        'email_verified_at',
        'login_otp_verified_at',
        'facebook_url',
        'x_url',
        'linkedin_url',
        'instagram_url',
        'country',
        'city_state',
        'postal_code',
        'tax_id',
        'username_changed_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'birth_of_date' => 'date',
        'login_otp_verified_at' => 'datetime',
        'role_id' => 'integer',
        'username_changed_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    /**
     * Check if user has a specific permission (via their role).
     *
     * OOAD: Permission checking using enum for type safety
     */
    public function hasPermission(Permission $permission): bool
    {
        if ($this->role === null) {
            return false;
        }

        // Super admin has all permissions
        if ($this->role->isSuperAdmin()) {
            return true;
        }

        return $this->role->hasPermission($permission->value);
    }

    /**
     * Check if user has a specific permission by slug string.
     * Falls back to enum check if slug exists.
     */
    public function hasPermissionSlug(string $slug): bool
    {
        // Check if it's a valid enum case
        if (Permission::exists($slug)) {
            return $this->hasPermission(Permission::fromSlug($slug));
        }

        // Fallback to database check for legacy or dynamic permissions
        if ($this->role === null) {
            return false;
        }

        return $this->role->hasPermission($slug);
    }

    /**
     * Check if user has any of the given permissions.
     *
     * @param array<int, Permission> $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions.
     *
     * @param array<int, Permission> $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user is Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role?->isSuperAdmin() ?? false;
    }

    /**
     * Get all permissions for the user.
     *
     * @return array<int, string>
     */
    public function getPermissions(): array
    {
        if ($this->role === null) {
            return [];
        }

        if ($this->role->isSuperAdmin()) {
            return Permission::allSlugs();
        }

        return $this->role->permissions()->pluck('slug')->toArray();
    }
}
