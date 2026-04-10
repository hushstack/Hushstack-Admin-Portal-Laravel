# Feature: Permissions and Role Management (2026-04-10)

## Overview
Implemented a comprehensive **Permission Management System** that enables Super Admins to define granular permissions and assign them to roles. This feature implements Clean Architecture / OOAD principles with a layered architecture (Controller → Service → Repository) and follows OWASP Top 10 security standards.

## Architecture Pattern

```
┌─────────────────────────────────────────────────────────────┐
│  PRESENTATION LAYER (Controllers)                           │
│  - PermissionController                                     │
│  - RolePermissionController                                 │
│  ├─ Thin controllers - only HTTP handling                   │
│  ├─ Delegates to Service Layer                              │
│  └─ Exception mapping to HTTP responses                     │
├─────────────────────────────────────────────────────────────┤
│  APPLICATION LAYER (Services)                               │
│  - PermissionService                                        │
│  - RolePermissionService                                    │
│  - CacheService                                             │
│  ├─ Business logic & rules                                  │
│  ├─ Transaction management                                  │
│  ├─ Caching strategy                                        │
│  └─ Domain exception throwing                               │
├─────────────────────────────────────────────────────────────┤
│  DOMAIN LAYER (Models, Exceptions, DTOs)                    │
│  - Permission (Model)                                       │
│  - CreatePermissionData (DTO)                               │
│  - UpdatePermissionData (DTO)                               │
│  - AssignPermissionsData (DTO)                              │
│  - Custom Exception classes                                 │
│  ├─ Domain entities & value objects                         │
│  ├─ Business rule violations as exceptions                  │
│  └─ Immutable DTOs for data transfer                        │
├─────────────────────────────────────────────────────────────┤
│  INFRASTRUCTURE LAYER (Repositories)                        │
│  - PermissionRepository                                     │
│  - RolePermissionRepository                                 │
│  ├─ Data access abstraction                                 │
│  ├─ Query optimization                                        │
│  └─ Eloquent ORM encapsulation                              │
├─────────────────────────────────────────────────────────────┤
│  CONTRACTS (Interfaces)                                     │
│  - PermissionRepositoryInterface                            │
│  - RolePermissionRepositoryInterface                        │
│  - PermissionServiceInterface                               │
│  - RolePermissionServiceInterface                           │
│  └─ Dependency Inversion Principle                          │
└─────────────────────────────────────────────────────────────┘
```

## Data Schema

### 1. Permissions Table (`permissions`)
- `id`: Primary key
- `slug`: Unique identifier (e.g., `users.create`, `posts.delete`)
- `name`: Human-readable name
- `description`: Optional explanation
- `timestamps`: `created_at` and `updated_at`
- **Indexes**: `idx_permissions_slug` for fast lookups

### 2. Role Permissions Pivot Table (`role_permissions`)
- `id`: Primary key
- `role_id`: Foreign key to `roles.id` (cascade delete)
- `permission_id`: Foreign key to `permissions.id` (cascade delete)
- `timestamps`: `created_at` and `updated_at`
- **Constraints**: Unique composite index on (`role_id`, `permission_id`)
- **Indexes**: `idx_role_permissions_lookup` for permission lookups

## Implementation Details

### Contracts (Interfaces)
- `App\Contracts\Repositories\PermissionRepositoryInterface`
- `App\Contracts\Repositories\RolePermissionRepositoryInterface`
- `App\Contracts\Services\PermissionServiceInterface`
- `App\Contracts\Services\RolePermissionServiceInterface`

### Repositories
- `App\Repositories\PermissionRepository`: Data access for permissions
- `App\Repositories\RolePermissionRepository`: Many-to-many relationship management

### Services
- `App\Services\PermissionService`: Permission CRUD with caching
- `App\Services\RolePermissionService`: Assignment/revocation with transactions
- `App\Services\CacheService`: Caching abstraction layer

### Controllers (Super Admin API)
- `App\Http\Controllers\Api\PermissionController`: Permission CRUD
- `App\Http\Controllers\Api\RolePermissionController`: Role-permission assignments

### DTOs (Data Transfer Objects)
- `App\DTOs\Permission\CreatePermissionData`: Immutable create data
- `App\DTOs\Permission\UpdatePermissionData`: Immutable update data
- `App\DTOs\Permission\AssignPermissionsData`: Immutable assignment data

### Custom Domain Exceptions
| Exception | HTTP Code | Use Case |
|-----------|-----------|----------|
| `PermissionNotFoundException` | 404 | Permission doesn't exist |
| `DuplicatePermissionException` | 422 | Slug already exists |
| `ProtectedPermissionException` | 403 | System permission modification |
| `PermissionInUseException` | 422 | Permission assigned to roles |
| `InvalidPermissionException` | 422 | Invalid permission IDs |
| `PermissionNotAssignedException` | 422 | Revoking unassigned permission |
| `ProtectedRoleException` | 403 | Super Admin role modification |

### Requests (Validation)
- `App\Http\Requests\Permission\StorePermissionRequest`
- `App\Http\Requests\Permission\UpdatePermissionRequest`
- `App\Http\Requests\Permission\AssignPermissionRequest`

### Resources (API Transformation)
- `App\Http\Resources\PermissionResource`: Serializes permission data
- `App\Http\Resources\RoleWithPermissionsResource`: Role with its permissions

### Middleware
- `App\Http\Middleware\EnsureSuperAdminRole`: Strict Super Admin verification
- Logs unauthorized access attempts for security monitoring

## Security & Authorization

### Access Control
- **Super Admin Only:** Only users with `super-admin` role slug can access endpoints
- **Middleware Chain:** `auth:sanctum` → `super_admin`
- **Rate Limiting:** 60 requests per minute to prevent brute force

### OWASP Compliance
| OWASP Category | Implementation |
|----------------|----------------|
| A01:2021 - Broken Access Control | Strict role verification, protected permission checks |
| A03:2021 - Injection | Input validation via FormRequest, regex sanitization, strip_tags |
| A07:2021 - Security Logging | All actions logged via ActivityLogger |
| A08:2021 - Software and Data Integrity | Atomic transactions for permission assignments |
| A09:2021 - Security Logging and Monitoring | Failed access attempts logged |

### Protected Resources
- **System Permissions:** `super-admin`, `system-admin` cannot be modified/deleted
- **Super Admin Role:** Cannot have all permissions revoked

## Performance Optimizations

1. **Caching Strategy**
   - Permissions list cached for 10 minutes
   - Role permissions cached per role
   - Cache invalidation on mutations

2. **Database Indexing**
   - `idx_permissions_slug` for slug lookups
   - `idx_role_permission_unique` for duplicate prevention
   - `idx_role_permissions_lookup` for role-based queries

3. **Query Optimization**
   - Eager loading for relationships
   - Select specific columns where possible
   - Bulk operations for permission sync

## Routes

All routes require Super Admin access and are rate-limited:

### Permission Management
- `GET /api/admin/permissions` - List all permissions
- `POST /api/admin/permissions` - Create permission
- `GET /api/admin/permissions/{permission}` - Show permission
- `PUT /api/admin/permissions/{permission}` - Update permission
- `DELETE /api/admin/permissions/{permission}` - Delete permission

### Role Permission Assignment
- `GET /api/admin/roles/{role}/permissions` - Get role's permissions
- `POST /api/admin/roles/{role}/permissions` - Assign permissions to role
- `DELETE /api/admin/roles/{role}/permissions/{permission}` - Revoke single permission
- `DELETE /api/admin/roles/{role}/permissions` - Revoke all permissions
- `GET /api/admin/permissions/{permission}/roles` - Get roles with permission

## API Testing & Examples

### 1. Create a Permission

**Endpoint:** `POST /api/admin/permissions`  
**Headers:** `Authorization: Bearer <super_admin_token>`, `Accept: application/json`  
**Body (JSON):**
```json
{
    "name": "Create Users",
    "slug": "users.create",
    "description": "Allows creating new user accounts"
}
```

**Expected Response (201 Created):**
```json
{
    "status_code": 201,
    "status": "ok",
    "message": "Permission created.",
    "data": {
        "id": 1,
        "name": "Create Users",
        "slug": "users.create",
        "description": "Allows creating new user accounts",
        "roles_count": null,
        "roles": null,
        "created_at": "2026-04-10 14:30:00",
        "updated_at": "2026-04-10 14:30:00"
    }
}
```

### 2. Assign Permissions to Role

**Endpoint:** `POST /api/admin/roles/{role}/permissions`  
**Headers:** `Authorization: Bearer <super_admin_token>`, `Accept: application/json`  
**Body (JSON):**
```json
{
    "permission_ids": [1, 2, 3, 4]
}
```

**Expected Response (200 OK):**
```json
{
    "status_code": 200,
    "status": "ok",
    "message": "Permissions assigned to role Admin.",
    "data": {
        "role": {
            "id": 4,
            "name": "Admin",
            "slug": "admin",
            "description": "Administrator role",
            "is_super_admin": false
        },
        "permissions": [
            {
                "id": 1,
                "name": "Create Users",
                "slug": "users.create",
                "description": "Allows creating new user accounts"
            }
        ],
        "sync_result": {
            "attached": [1, 2],
            "detached": [5],
            "updated": []
        }
    }
}
```

### 3. List Role Permissions

**Endpoint:** `GET /api/admin/roles/4/permissions`  
**Expected Response (200 OK):**
```json
{
    "status_code": 200,
    "status": "ok",
    "message": "Permissions for role Admin loaded.",
    "data": [
        {
            "id": 1,
            "name": "Create Users",
            "slug": "users.create",
            "description": "Allows creating new user accounts"
        },
        {
            "id": 2,
            "name": "Delete Users",
            "slug": "users.delete",
            "description": "Allows deleting user accounts"
        }
    ]
}
```

### 4. Revoke Permission from Role

**Endpoint:** `DELETE /api/admin/roles/4/permissions/1`  
**Expected Response (200 OK):**
```json
{
    "status_code": 200,
    "status": "ok",
    "message": "Permission Create Users revoked from role Admin.",
    "data": null
}
```

### 5. Error Response (Duplicate Slug)

**Endpoint:** `POST /api/admin/permissions`  
**Body (JSON):**
```json
{
    "name": "Create Users",
    "slug": "users.create"
}
```

**Expected Response (422 Unprocessable Entity):**
```json
{
    "status_code": 422,
    "status": "error",
    "message": "Permission slug 'users.create' already exists.",
    "errors": null
}
```

### 6. Error Response (Protected Permission)

**Endpoint:** `DELETE /api/admin/permissions/1` (assuming slug is `super-admin`)  
**Expected Response (403 Forbidden):**
```json
{
    "status_code": 403,
    "status": "error",
    "message": "Cannot delete protected permission 'super-admin'.",
    "errors": null
}
```

## SOLID Principles Applied

| Principle | Implementation |
|-----------|----------------|
| **S**ingle Responsibility | Controllers = HTTP, Services = Business Logic, Repositories = Data Access |
| **O**pen/Closed | Interfaces allow extending without modifying existing code |
| **L**iskov Substitution | Repository/Service implementations interchangeable via interfaces |
| **I**nterface Segregation | Separate interfaces for Permission vs RolePermission concerns |
| **D**ependency Inversion | Controllers depend on interfaces, not concrete classes |

## Design Patterns Used

- **Repository Pattern:** Data access abstraction
- **Service Layer Pattern:** Business logic encapsulation
- **DTO Pattern:** Immutable data transfer objects
- **Dependency Injection:** Constructor injection throughout
- **Factory Pattern:** DTO `fromRequest()` methods

## Verification

- **Migrations:** 
  - `2026_04_10_000001_create_permissions_table.php`
  - `2026_04_10_000002_create_role_permissions_table.php`
- **Middleware Registration:** `super_admin` alias added to `App\Http\Kernel.php`
- **Service Provider Bindings:** All interfaces bound in `App\Providers\AppServiceProvider.php`

## Usage Instructions

### Creating Default Permissions
Run seeder or use API to create common permissions:
```php
// Example permissions
$permissions = [
    ['name' => 'View Users', 'slug' => 'users.view'],
    ['name' => 'Create Users', 'slug' => 'users.create'],
    ['name' => 'Edit Users', 'slug' => 'users.edit'],
    ['name' => 'Delete Users', 'slug' => 'users.delete'],
    ['name' => 'View Roles', 'slug' => 'roles.view'],
    ['name' => 'Manage Permissions', 'slug' => 'permissions.manage'],
];
```

### Assigning Permissions
Only Super Admin can assign permissions. To check if a user has permission:
```php
$user->role->hasPermission('users.create');
```

## Migration Command
```bash
php artisan migrate
```
