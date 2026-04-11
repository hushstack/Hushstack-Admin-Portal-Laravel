# Permission System Workflow Guide

> **Tag this file when working on new features** - Every new feature requires permission seeding!

---

## 🎯 Quick Reference

When adding a **NEW FEATURE**, you **MUST**:

1. **Add permissions to `app/Enums/Permission.php`**
2. **Run `php artisan migrate` to sync to DB** (or use `PermissionRegistrar` directly)
3. **Apply middleware to routes** in `routes/api.php`
4. **Update documentation** if needed

---

## 📁 File Locations

| Purpose | File Path |
|---------|-----------|
| **Permission Enum** | `app/Enums/Permission.php` |
| **Permission Sync** | `app/Services/PermissionRegistrar.php` |
| **Seeder** | `database/seeders/PermissionSeeder.php` |
| **Migration** | `database/migrations/2026_04_10_000003_sync_permissions_to_database.php` |
| **Middleware** | `app/Http/Middleware/CheckPermission.php` |
| **Routes** | `routes/api.php` |

---

## 🔑 Step-by-Step: Adding New Feature Permissions

### Step 1: Add to Permission Enum

Open `app/Enums/Permission.php` and add new cases:

```php
enum Permission: string
{
    // ... existing permissions ...
    
    // NEW FEATURE: Inventory Management
    case INVENTORY_VIEW = 'inventory.view';
    case INVENTORY_CREATE = 'inventory.create';
    case INVENTORY_UPDATE = 'inventory.update';
    case INVENTORY_DELETE = 'inventory.delete';
    case INVENTORY_MANAGE = 'inventory.manage';
    
    public function label(): string
    {
        return match($this) {
            // ... existing labels ...
            self::INVENTORY_VIEW => 'View Inventory',
            self::INVENTORY_CREATE => 'Create Inventory Items',
            self::INVENTORY_UPDATE => 'Update Inventory Items',
            self::INVENTORY_DELETE => 'Delete Inventory Items',
            self::INVENTORY_MANAGE => 'Manage Inventory',
        };
    }
    
    public function description(): string
    {
        return match($this) {
            // ... existing descriptions ...
            self::INVENTORY_VIEW => 'Can view inventory items',
            self::INVENTORY_CREATE => 'Can create new inventory items',
            self::INVENTORY_UPDATE => 'Can edit existing inventory items',
            self::INVENTORY_DELETE => 'Can delete inventory items',
            self::INVENTORY_MANAGE => 'Full inventory management access',
        };
    }
}
```

### Step 2: Sync to Database

**Option A: Run Migration (Recommended for new environments)**
```bash
php artisan migrate
```

**Option B: Run Seeder (For existing environments)**
```bash
php artisan db:seed --class=PermissionSeeder
```

**Option C: Programmatic Sync (In Code)**
```php
use App\Services\PermissionRegistrar;

app(PermissionRegistrar::class)->syncToDatabase();
```

### Step 3: Apply Middleware to Routes

In `routes/api.php`:

```php
use App\Enums\Permission;

Route::middleware(['auth:sanctum'])
    ->prefix('admin')
    ->group(function () {
        
        // NEW FEATURE: Inventory
        Route::get('/inventory', [InventoryController::class, 'index'])
            ->middleware('permission:' . Permission::INVENTORY_VIEW->value)
            ->name('inventory.index');
            
        Route::post('/inventory', [InventoryController::class, 'store'])
            ->middleware('permission:' . Permission::INVENTORY_CREATE->value)
            ->name('inventory.store');
            
        Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])
            ->middleware('permission:' . Permission::INVENTORY_UPDATE->value)
            ->name('inventory.update');
            
        Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])
            ->middleware('permission:' . Permission::INVENTORY_DELETE->value)
            ->name('inventory.destroy');
            
        // Or use any_permission for multiple allowed permissions
        Route::post('/inventory/bulk', [InventoryController::class, 'bulkAction'])
            ->middleware('any_permission:' . Permission::INVENTORY_MANAGE->value . ',' . Permission::INVENTORY_CREATE->value)
            ->name('inventory.bulk');
    });
```

### Step 4: Update Role Permissions (Optional)

If you want to auto-assign to specific roles, modify seeder:

```php
// database/seeders/RoleSeeder.php
$adminRole->syncPermissionEnums([
    // ... existing ...
    Permission::INVENTORY_VIEW,
    Permission::INVENTORY_CREATE,
    Permission::INVENTORY_UPDATE,
    Permission::INVENTORY_DELETE,
]);
```

---

## 🧩 Permission Naming Convention

```
{resource}.{action}

Examples:
- users.view
- users.create
- users.update
- users.delete
- users.manage (wildcard/combined)

- orders.view
- orders.create
- orders.update
- orders.delete
- orders.manage
```

### Standard Actions

| Action | Description |
|--------|-------------|
| `view` | List and view individual items |
| `create` | Create new items |
| `update` | Edit existing items |
| `delete` | Delete/soft-delete items |
| `manage` | Full CRUD access (all above) |

---

## ✅ Checklist for New Features

- [ ] Added permission cases to `Permission.php` enum
- [ ] Added `label()` method return for each permission
- [ ] Added `description()` method return for each permission
- [ ] Ran `php artisan migrate` or `PermissionRegistrar::syncToDatabase()`
- [ ] Added middleware to routes in `api.php`
- [ ] Tested with Super Admin (has all permissions)
- [ ] Tested with limited role (specific permissions only)

---

## 🚀 Quick Commands

```bash
# Sync permissions to database
php artisan migrate

# Or seed directly
php artisan db:seed --class=PermissionSeeder

# Check permissions in database
php artisan tinker
>>> \App\Models\Permission::all()->pluck('slug')

# Clear permission cache (if using Redis)
php artisan cache:clear
```

---

## 📝 Example: Complete New Feature Setup

**Feature**: Warehouse Management

### 1. Add to Permission Enum (`app/Enums/Permission.php`)

```php
case WAREHOUSE_VIEW = 'warehouse.view';
case WAREHOUSE_CREATE = 'warehouse.create';
case WAREHOUSE_UPDATE = 'warehouse.update';
case WAREHOUSE_DELETE = 'warehouse.delete';
```

### 2. Add Labels

```php
self::WAREHOUSE_VIEW => 'View Warehouses',
self::WAREHOUSE_CREATE => 'Create Warehouses',
self::WAREHOUSE_UPDATE => 'Update Warehouses',
self::WAREHOUSE_DELETE => 'Delete Warehouses',
```

### 3. Add Descriptions

```php
self::WAREHOUSE_VIEW => 'Can view warehouse locations',
self::WAREHOUSE_CREATE => 'Can create new warehouse locations',
self::WAREHOUSE_UPDATE => 'Can edit warehouse details',
self::WAREHOUSE_DELETE => 'Can remove warehouse locations',
```

### 4. Run Sync

```bash
php artisan db:seed --class=PermissionSeeder
```

### 5. Add Routes (`routes/api.php`)

```php
Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin')
    ->group(function () {
        
        Route::apiResource('warehouses', WarehouseController::class)
            ->middleware('permission:warehouse.view');
            
        // Or granular permissions per action
        Route::get('/warehouses', [WarehouseController::class, 'index'])
            ->middleware('permission:' . Permission::WAREHOUSE_VIEW->value);
        Route::post('/warehouses', [WarehouseController::class, 'store'])
            ->middleware('permission:' . Permission::WAREHOUSE_CREATE->value);
    });
```

---

## 🔍 Debugging

### Check if Permission Exists in DB

```bash
php artisan tinker
>>> \App\Models\Permission::where('slug', 'warehouse.view')->first();
```

### Check User Permissions

```bash
php artisan tinker
>>> $user = \App\Models\User::first();
>>> $user->role->getPermissionEnums();
```

### Force Resync All Permissions

```bash
php artisan tinker
>>> app(\App\Services\PermissionRegistrar::class)->syncToDatabase();
```

---

## 📚 Related Documentation

- `docs/Features/permission-testing-guide.md` - API testing guide
- `docs/Features/permission-usage-examples.md` - Usage examples
- `app/Enums/Permission.php` - Master permission list (41 permissions)

---

**Remember**: Every new feature needs permissions! Tag this file when creating new features.
