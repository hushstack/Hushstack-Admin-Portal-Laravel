# Admin Feature Context (as of 2026-03-04)

## Files reviewed
- `app/Http/Kernel.php`
- `app/Http/Controllers/Api/RoleController.php`
- `app/Http/Controllers/Api/UserRoleController.php`
- `app/Http/Middleware/EnsureAdminRole.php`
- `routes/api.php`

## Key findings
- The `admin` middleware alias is registered in `app/Http/Kernel.php` and maps to `App\Http\Middleware\EnsureAdminRole`.
- `EnsureAdminRole` checks the authenticated user's role slug against `Role::ADMIN_SLUG` and returns a JSON 403 error if not admin.
- Admin routes are grouped under `routes/api.php` with `auth:sanctum` + `admin` middleware:
  - `Route::apiResource('roles', RoleController::class)->except(['show'])`
  - `Route::post('users/{user}/role', [UserRoleController::class, 'assign'])`
- `RoleController` handles CRUD for roles with slug uniqueness checks and prevents deleting roles that have users assigned.
- `UserRoleController` assigns a `role_id` to a user via `AssignRoleRequest`.

## Note
- There is a `RoleController` (not `RollController`).
