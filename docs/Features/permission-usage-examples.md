# Permission Usage Examples

## Hardcoded Permission System

All permissions are defined in `@/app/Enums/Permission.php` as enum cases.

### Sync Permissions to Database

Run seeder to sync hardcoded permissions to database:
```bash
php artisan db:seed --class=PermissionSeeder
```

Or add to `DatabaseSeeder.php`:
```php
$this->call([
    PermissionSeeder::class,
]);
```

---

## Usage in Controllers

### Check Permission in Controller Method
```php
use App\Enums\Permission;

public function store(Request $request)
{
    // Check if user has permission
    if (!$request->user()->hasPermission(Permission::USERS_CREATE)) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    // ... create user
}
```

### Check Multiple Permissions
```php
// User needs ANY of these permissions
if ($request->user()->hasAnyPermission([Permission::USERS_CREATE, Permission::USERS_EDIT])) {
    // Allow access
}

// User needs ALL of these permissions
if ($request->user()->hasAllPermissions([Permission::USERS_CREATE, Permission::USERS_EDIT])) {
    // Allow access
}
```

---

## Usage in Routes

### Single Permission Middleware
```php
use App\Enums\Permission;

Route::middleware(['auth:sanctum', 'permission:' . Permission::USERS_CREATE->value])
    ->post('/users', [UserController::class, 'store']);
```

### Any Permission Middleware
```php
Route::middleware([
    'auth:sanctum',
    'any_permission:' . Permission::USERS_CREATE->value . ',' . Permission::USERS_EDIT->value
])->post('/users', [UserController::class, 'store']);
```

### Group Routes by Permission
```php
Route::middleware(['auth:sanctum'])->group(function () {

    // Users with 'users.view' can list users
    Route::middleware(['permission:' . Permission::USERS_VIEW->value])
        ->get('/users', [UserController::class, 'index']);

    // Users with 'users.create' can add users
    Route::middleware(['permission:' . Permission::USERS_CREATE->value])
        ->post('/users', [UserController::class, 'store']);

    // Users with 'users.edit' can update users
    Route::middleware(['permission:' . Permission::USERS_EDIT->value])
        ->put('/users/{user}', [UserController::class, 'update']);

    // Users with 'users.delete' can delete users
    Route::middleware(['permission:' . Permission::USERS_DELETE->value])
        ->delete('/users/{user}', [UserController::class, 'destroy']);
});
```

---

## Usage in Blade Views

```blade
@can(Permission::USERS_CREATE->value)
    <a href="{{ route('users.create') }}">Create User</a>
@endcan

@if(auth()->user()->hasPermission(App\Enums\Permission::USERS_DELETE))
    <button>Delete</button>
@endif
```

---

## Assigning Permissions to Roles

### In Code (Migrations/Seeders)
```php
use App\Enums\Permission;
use App\Models\Role;

$adminRole = Role::where('slug', 'admin')->first();

// Assign specific permissions
$adminRole->syncPermissionEnums([
    Permission::USERS_VIEW,
    Permission::USERS_CREATE,
    Permission::USERS_EDIT,
    Permission::USERS_DELETE,
    Permission::ROLES_VIEW,
    Permission::PERMISSIONS_VIEW,
]);
```

### Via API (Super Admin only)
```bash
curl -X POST \
  https://api.example.com/admin/roles/4/permissions \
  -H "Authorization: Bearer {super_admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "permission_ids": [1, 2, 3, 4]
  }'
```

---

## Checking User Permissions

### Get All User Permissions
```php
$permissions = $user->getPermissions();
// Returns: ['users.view', 'users.create', ...]
```

### Check if Super Admin
```php
if ($user->isSuperAdmin()) {
    // Has ALL permissions
}
```

### Check Role Permissions
```php
// Check if role has permission
$role->hasPermissionEnum(Permission::USERS_CREATE);

// Check if role has any permission
$role->hasAnyPermission([Permission::USERS_CREATE, Permission::USERS_EDIT]);

// Check if role has all permissions
$role->hasAllPermissions([Permission::USERS_CREATE, Permission::USERS_EDIT]);
```

---

## Available Permissions

See `@/app/Enums/Permission.php` for full list. Common ones:

| Permission | Slug |
|------------|------|
| `Permission::USERS_VIEW` | `users.view` |
| `Permission::USERS_CREATE` | `users.create` |
| `Permission::USERS_EDIT` | `users.edit` |
| `Permission::USERS_DELETE` | `users.delete` |
| `Permission::ROLES_VIEW` | `roles.view` |
| `Permission::PERMISSIONS_ASSIGN` | `permissions.assign` |
| `Permission::PROJECTS_CREATE` | `projects.create` |
| `Permission::SETTINGS_EDIT` | `settings.edit` |

---

## Adding New Permissions

1. Add case to `@/app/Enums/Permission.php`:
```php
case REPORTS_VIEW = 'reports.view';
```

2. Add label and description:
```php
public function label(): string
{
    return match($this) {
        // ... existing cases
        self::REPORTS_VIEW => 'View Reports',
    };
}

public function description(): string
{
    return match($this) {
        // ... existing cases
        self::REPORTS_VIEW => 'Access to view system reports',
    };
}
```

3. Run seeder to sync to database:
```bash
php artisan db:seed --class=PermissionSeeder
```

4. Assign to roles as needed via API or seeder.
