<?php

namespace App\Enums;

/**
 * Permission Enum
 *
 * OOAD: Hardcoded permissions as enum cases
 * Clean Architecture: Single source of truth for all permissions
 * All permissions are defined here and synced to database via seeder
 */
enum Permission: string
{
    // User Management
    case USERS_VIEW = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_EDIT = 'users.edit';
    case USERS_DELETE = 'users.delete';
    case USERS_EXPORT = 'users.export';

    // Role Management
    case ROLES_VIEW = 'roles.view';
    case ROLES_CREATE = 'roles.create';
    case ROLES_EDIT = 'roles.edit';
    case ROLES_DELETE = 'roles.delete';

    // Permission Management
    case PERMISSIONS_VIEW = 'permissions.view';
    case PERMISSIONS_ASSIGN = 'permissions.assign';
    case PERMISSIONS_REVOKE = 'permissions.revoke';

    // Member Management
    case MEMBERS_VIEW = 'members.view';
    case MEMBERS_CREATE = 'members.create';
    case MEMBERS_EDIT = 'members.edit';
    case MEMBERS_DELETE = 'members.delete';

    // Position Management
    case POSITIONS_VIEW = 'positions.view';
    case POSITIONS_CREATE = 'positions.create';
    case POSITIONS_EDIT = 'positions.edit';
    case POSITIONS_DELETE = 'positions.delete';

    // Project Management
    case PROJECTS_VIEW = 'projects.view';
    case PROJECTS_CREATE = 'projects.create';
    case PROJECTS_EDIT = 'projects.edit';
    case PROJECTS_DELETE = 'projects.delete';
    case PROJECTS_PUBLISH = 'projects.publish';

    // Product Management
    case PRODUCTS_VIEW = 'products.view';
    case PRODUCTS_CREATE = 'products.create';
    case PRODUCTS_EDIT = 'products.edit';
    case PRODUCTS_DELETE = 'products.delete';

    // Category Management
    case CATEGORIES_VIEW = 'categories.view';
    case CATEGORIES_CREATE = 'categories.create';
    case CATEGORIES_EDIT = 'categories.edit';
    case CATEGORIES_DELETE = 'categories.delete';

    // Brand Management
    case BRANDS_VIEW = 'brands.view';
    case BRANDS_CREATE = 'brands.create';
    case BRANDS_EDIT = 'brands.edit';
    case BRANDS_DELETE = 'brands.delete';

    // Department Management
    case DEPARTMENTS_VIEW = 'departments.view';
    case DEPARTMENTS_CREATE = 'departments.create';
    case DEPARTMENTS_EDIT = 'departments.edit';
    case DEPARTMENTS_DELETE = 'departments.delete';

    // User Requests
    case USER_REQUESTS_VIEW = 'user_requests.view';
    case USER_REQUESTS_APPROVE = 'user_requests.approve';
    case USER_REQUESTS_REJECT = 'user_requests.reject';

    // User Activities
    case USER_ACTIVITIES_VIEW = 'user_activities.view';

    // System Logs
    case LOGS_VIEW = 'logs.view';
    case LOGS_DELETE = 'logs.delete';

    // Settings
    case SETTINGS_VIEW = 'settings.view';
    case SETTINGS_EDIT = 'settings.edit';

    // Collection Management
    case COLLECTIONS_VIEW = 'collections.view';
    case COLLECTIONS_CREATE = 'collections.create';
    case COLLECTIONS_EDIT = 'collections.edit';
    case COLLECTIONS_DELETE = 'collections.delete';

    // Branch Management
    case BRANCHES_VIEW = 'branches.view';
    case BRANCHES_CREATE = 'branches.create';
    case BRANCHES_EDIT = 'branches.edit';
    case BRANCHES_DELETE = 'branches.delete';

    // Commit Management
    case COMMITS_VIEW = 'commits.view';
    case COMMITS_CREATE = 'commits.create';
    case COMMITS_EDIT = 'commits.edit';
    case COMMITS_DELETE = 'commits.delete';

    /**
     * Get human-readable name for permission.
     */
    public function label(): string
    {
        return match($this) {
            self::USERS_VIEW => 'View Users',
            self::USERS_CREATE => 'Create Users',
            self::USERS_EDIT => 'Edit Users',
            self::USERS_DELETE => 'Delete Users',
            self::USERS_EXPORT => 'Export Users',

            self::ROLES_VIEW => 'View Roles',
            self::ROLES_CREATE => 'Create Roles',
            self::ROLES_EDIT => 'Edit Roles',
            self::ROLES_DELETE => 'Delete Roles',

            self::PERMISSIONS_VIEW => 'View Permissions',
            self::PERMISSIONS_ASSIGN => 'Assign Permissions',
            self::PERMISSIONS_REVOKE => 'Revoke Permissions',

            self::MEMBERS_VIEW => 'View Members',
            self::MEMBERS_CREATE => 'Create Members',
            self::MEMBERS_EDIT => 'Edit Members',
            self::MEMBERS_DELETE => 'Delete Members',

            self::POSITIONS_VIEW => 'View Positions',
            self::POSITIONS_CREATE => 'Create Positions',
            self::POSITIONS_EDIT => 'Edit Positions',
            self::POSITIONS_DELETE => 'Delete Positions',

            self::PROJECTS_VIEW => 'View Projects',
            self::PROJECTS_CREATE => 'Create Projects',
            self::PROJECTS_EDIT => 'Edit Projects',
            self::PROJECTS_DELETE => 'Delete Projects',
            self::PROJECTS_PUBLISH => 'Publish Projects',

            self::PRODUCTS_VIEW => 'View Products',
            self::PRODUCTS_CREATE => 'Create Products',
            self::PRODUCTS_EDIT => 'Edit Products',
            self::PRODUCTS_DELETE => 'Delete Products',

            self::CATEGORIES_VIEW => 'View Categories',
            self::CATEGORIES_CREATE => 'Create Categories',
            self::CATEGORIES_EDIT => 'Edit Categories',
            self::CATEGORIES_DELETE => 'Delete Categories',

            self::BRANDS_VIEW => 'View Brands',
            self::BRANDS_CREATE => 'Create Brands',
            self::BRANDS_EDIT => 'Edit Brands',
            self::BRANDS_DELETE => 'Delete Brands',

            self::DEPARTMENTS_VIEW => 'View Departments',
            self::DEPARTMENTS_CREATE => 'Create Departments',
            self::DEPARTMENTS_EDIT => 'Edit Departments',
            self::DEPARTMENTS_DELETE => 'Delete Departments',

            self::USER_REQUESTS_VIEW => 'View User Requests',
            self::USER_REQUESTS_APPROVE => 'Approve User Requests',
            self::USER_REQUESTS_REJECT => 'Reject User Requests',

            self::USER_ACTIVITIES_VIEW => 'View User Activities',

            self::LOGS_VIEW => 'View System Logs',
            self::LOGS_DELETE => 'Delete System Logs',

            self::SETTINGS_VIEW => 'View Settings',
            self::SETTINGS_EDIT => 'Edit Settings',

            self::COLLECTIONS_VIEW => 'View Collections',
            self::COLLECTIONS_CREATE => 'Create Collections',
            self::COLLECTIONS_EDIT => 'Edit Collections',
            self::COLLECTIONS_DELETE => 'Delete Collections',

            self::BRANCHES_VIEW => 'View Branches',
            self::BRANCHES_CREATE => 'Create Branches',
            self::BRANCHES_EDIT => 'Edit Branches',
            self::BRANCHES_DELETE => 'Delete Branches',

            self::COMMITS_VIEW => 'View Commits',
            self::COMMITS_CREATE => 'Create Commits',
            self::COMMITS_EDIT => 'Edit Commits',
            self::COMMITS_DELETE => 'Delete Commits',
        };
    }

    /**
     * Get description for permission.
     */
    public function description(): string
    {
        return match($this) {
            self::USERS_VIEW => 'View user profiles and lists',
            self::USERS_CREATE => 'Create new user accounts',
            self::USERS_EDIT => 'Edit existing user accounts',
            self::USERS_DELETE => 'Delete user accounts',
            self::USERS_EXPORT => 'Export user data to files',

            self::ROLES_VIEW => 'View system roles',
            self::ROLES_CREATE => 'Create new roles',
            self::ROLES_EDIT => 'Edit existing roles',
            self::ROLES_DELETE => 'Delete roles',

            self::PERMISSIONS_VIEW => 'View all system permissions',
            self::PERMISSIONS_ASSIGN => 'Assign permissions to roles',
            self::PERMISSIONS_REVOKE => 'Revoke permissions from roles',

            self::MEMBERS_VIEW => 'View organizational members',
            self::MEMBERS_CREATE => 'Add new members',
            self::MEMBERS_EDIT => 'Edit member information',
            self::MEMBERS_DELETE => 'Remove members',

            self::POSITIONS_VIEW => 'View job positions',
            self::POSITIONS_CREATE => 'Create new positions',
            self::POSITIONS_EDIT => 'Edit position details',
            self::POSITIONS_DELETE => 'Delete positions',

            self::PROJECTS_VIEW => 'View projects',
            self::PROJECTS_CREATE => 'Create new projects',
            self::PROJECTS_EDIT => 'Edit project details',
            self::PROJECTS_DELETE => 'Delete projects',
            self::PROJECTS_PUBLISH => 'Publish/unpublish projects',

            self::PRODUCTS_VIEW => 'View products',
            self::PRODUCTS_CREATE => 'Create new products',
            self::PRODUCTS_EDIT => 'Edit product details',
            self::PRODUCTS_DELETE => 'Delete products',

            self::CATEGORIES_VIEW => 'View categories',
            self::CATEGORIES_CREATE => 'Create new categories',
            self::CATEGORIES_EDIT => 'Edit category details',
            self::CATEGORIES_DELETE => 'Delete categories',

            self::BRANDS_VIEW => 'View brands',
            self::BRANDS_CREATE => 'Create new brands',
            self::BRANDS_EDIT => 'Edit brand details',
            self::BRANDS_DELETE => 'Delete brands',

            self::DEPARTMENTS_VIEW => 'View departments',
            self::DEPARTMENTS_CREATE => 'Create new departments',
            self::DEPARTMENTS_EDIT => 'Edit department details',
            self::DEPARTMENTS_DELETE => 'Delete departments',

            self::USER_REQUESTS_VIEW => 'View user requests',
            self::USER_REQUESTS_APPROVE => 'Approve user requests',
            self::USER_REQUESTS_REJECT => 'Reject user requests',

            self::USER_ACTIVITIES_VIEW => 'View user activity logs',

            self::LOGS_VIEW => 'View system logs',
            self::LOGS_DELETE => 'Delete old system logs',

            self::SETTINGS_VIEW => 'View system settings',
            self::SETTINGS_EDIT => 'Modify system settings',

            self::COLLECTIONS_VIEW => 'View version control collections',
            self::COLLECTIONS_CREATE => 'Create new collections',
            self::COLLECTIONS_EDIT => 'Edit collection details',
            self::COLLECTIONS_DELETE => 'Delete collections',

            self::BRANCHES_VIEW => 'View version control branches',
            self::BRANCHES_CREATE => 'Create new branches',
            self::BRANCHES_EDIT => 'Edit branch details',
            self::BRANCHES_DELETE => 'Delete branches',

            self::COMMITS_VIEW => 'View version control commits',
            self::COMMITS_CREATE => 'Create new commits',
            self::COMMITS_EDIT => 'Edit commit details',
            self::COMMITS_DELETE => 'Delete commits',
        };
    }

    /**
     * Get all permissions as array for seeding.
     *
     * @return array<int, array<string, string>>
     */
    public static function toSeedArray(): array
    {
        return array_map(
            fn (self $permission) => [
                'slug' => $permission->value,
                'name' => $permission->label(),
                'description' => $permission->description(),
            ],
            self::cases()
        );
    }

    /**
     * Get permission by slug.
     */
    public static function fromSlug(string $slug): ?self
    {
        return self::tryFrom($slug);
    }

    /**
     * Check if permission exists.
     */
    public static function exists(string $slug): bool
    {
        return self::tryFrom($slug) !== null;
    }

    /**
     * Get all permission slugs.
     *
     * @return array<int, string>
     */
    public static function allSlugs(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }
}
