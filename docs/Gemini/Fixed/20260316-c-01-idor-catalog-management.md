# Security Issue: Broken Object Level Authorization (BOLA/IDOR) in Catalog Management

**ID:** 20260316-c-01-idor-catalog-management
**Severity:** CRITICAL
**Date Fixed:** March 16, 2026

## Problem Description
A Broken Object Level Authorization (BOLA / IDOR) vulnerability existed in the catalog management endpoints. The `update()` and `destroy()` methods in the `ProductController`, `DepartmentController`, `CategoryController`, and `BrandController` only enforced resource ownership checks if the authenticated user was a Partner (`$this->isPartner($request)`). Normal users (`Role::USER_SLUG`) completely bypassed this check. 

This allowed any authenticated normal user to maliciously modify or delete catalog items belonging to any other user, including administrators, simply by knowing the resource ID.

## Files Changed
- `app/Http/Controllers/Api/ProductController.php`
- `app/Http/Controllers/Api/DepartmentController.php`
- `app/Http/Controllers/Api/CategoryController.php`
- `app/Http/Controllers/Api/BrandController.php`

## Solution Applied
The ownership validation logic was updated to apply to both Partners and Normal Users. We explicitly added `$this->isUser($request)` (or `request()`) to the conditional check.

**Before:**
```php
if ($this->isPartner($request) && $model->user_id !== $request->user()->id) {
    return $this->errorResponse('Forbidden.', 403);
}
```

**After:**
```php
// Fix IDOR/BOLA: Ensure normal users also pass ownership checks
if (($this->isPartner($request) || $this->isUser($request)) && $model->user_id !== $request->user()->id) {
    return $this->errorResponse('Forbidden.', 403);
}
```
*(This change was applied to the `update` and `destroy` methods across all four catalog controllers).*