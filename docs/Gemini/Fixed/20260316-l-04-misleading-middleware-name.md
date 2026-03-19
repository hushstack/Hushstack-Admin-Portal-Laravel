# Security Issue: Misleading Middleware Name

**ID:** 20260316-l-04-misleading-middleware-name
**Severity:** LOW
**Date Fixed:** March 16, 2026

## Problem Description
The application utilized a middleware named `EnsureCatalogEditorRole` to protect catalog creation routes. Despite its name implying elevated privileges (like an admin or dedicated editor), the middleware explicitly allowed the `USER_SLUG` role to pass. 

While this was functionally correct according to the business logic (which permits normal users to create a limited number of catalog items), the misleading name posed a long-term architectural and security risk. Future developers could mistakenly apply this middleware to sensitive administrative routes, incorrectly assuming it protected against normal users.

## Files Changed
- `app/Http/Middleware/EnsureCatalogEditorRole.php` (Renamed to `EnsureActiveCatalogUserRole.php`)
- `app/Http/Kernel.php`
- `routes/api.php`

## Solution Applied
1. **Renamed Class:** Renamed the middleware file and class from `EnsureCatalogEditorRole` to `EnsureActiveCatalogUserRole` to accurately reflect that it allows active users who participate in catalog management.
2. **Updated Kernel Alias:** Updated the alias array in `app/Http/Kernel.php` from `'catalog_editor'` to `'active_catalog_user'`.
3. **Updated Routes:** Replaced the middleware reference in `routes/api.php`.

**Code Snippet (app/Http/Kernel.php):**
```php
    protected $middlewareAliases = [
        // ...
        // Renamed from 'catalog_editor' to 'active_catalog_user' to avoid misleading privilege assumptions
        'active_catalog_user' => \App\Http\Middleware\EnsureActiveCatalogUserRole::class,
        // ...
    ];
```
