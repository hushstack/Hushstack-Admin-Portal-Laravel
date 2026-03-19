# Security Audit Report - Hushstack Admin Portal

**Date:** March 16, 2026
**Target:** Hushstack Admin Portal (Laravel API)
**Scope:** Security Audit (Authentication, Authorization, Business Logic, and Input Validation)

---

## Executive Summary
A comprehensive security review of the Hushstack Admin Portal backend reveals several critical vulnerabilities, primarily concerning **Broken Object Level Authorization (BOLA/IDOR)** and **Email Denial of Service (DoS)**. Due to missing authorization checks, regular users can manipulate the entire catalog. Additionally, the lack of rate-limiting on OTP endpoints allows for unchecked email spam.

Immediate remediation is required to secure user data and application infrastructure.

---

## Findings Details

### 1. [CRITICAL] Broken Object Level Authorization (BOLA/IDOR) in Catalog Management
**Vulnerability Type:** Privilege Escalation / IDOR
**Location:** 
- `app/Http/Controllers/Api/ProductController.php` (Lines 94, 139)
- `app/Http/Controllers/Api/DepartmentController.php` (Lines 82, 117)
- `app/Http/Controllers/Api/CategoryController.php` (Lines 82, 117)
- `app/Http/Controllers/Api\BrandController.php` (Lines 80, 115)

**Description:**
The `update()` and `destroy()` methods in all catalog controllers enforce ownership checks by ensuring that only the creator of a resource can modify it. However, the condition only validates if the requester is a Partner (`$this->isPartner($request)`). For normal users (`Role::USER_SLUG`), the `isPartner()` method evaluates to `false`, causing the ownership check to be completely skipped.

As a result, any authenticated user can bypass the restriction and arbitrarily modify or delete products, departments, categories, and brands belonging to other users (including administrators).

**Exploit Scenario:**
1. A normal user creates an account and obtains a Bearer token.
2. The user sends a `DELETE /api/admin/products/{id}` request with the ID of an administrator's product.
3. Because the user is not a Partner, the check `if ($this->isPartner(request()) && ...)` fails, the execution continues, and the product is deleted.

**Solution:**
Include the `$this->isUser($request)` check in the ownership condition across all catalog controllers.
```php
// Before:
if ($this->isPartner($request) && $product->user_id !== $request->user()->id) {
    return $this->errorResponse('Forbidden.', 403);
}

// After:
if (($this->isPartner($request) || $this->isUser($request)) && $model->user_id !== $request->user()->id) {
    return $this->errorResponse('Forbidden.', 403);
}
```
*Note: A more robust and scalable solution would be migrating to Laravel Policies (`$this->authorize('update', $product)`).*

---

### 2. [HIGH] Email Bombing / OTP Denial of Service
**Vulnerability Type:** Business Logic Flaw / DoS
**Location:** 
- `app/Services/OtpService.php` (Method: `send`)
- `app/Http/Controllers/Api/AuthController.php` (Method: `forgotPassword`)

**Description:**
The application correctly implements a 60-second cooldown on the `resendOtp` endpoint to prevent email spam. However, the `forgotPassword` endpoint directly calls the `send()` method in `OtpService`. The `send()` method forcefully deletes any existing active OTP and creates a new one, immediately queueing a new email. It does not check the `resendCooldownSeconds`.

An attacker can repeatedly request password resets for a target user without limitation, causing an email flood, exhausting the application's SMTP quota, and potentially resulting in the domain being blacklisted.

**Solution:**
Apply a Laravel Throttle Middleware to the sensitive authentication routes in `routes/api.php`, particularly for forgot password and OTP sending.
```php
// In routes/api.php
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
```
Additionally, consider updating the `send()` method in `OtpService` to enforce the cooldown programmatically before dispatching a new email.

---

### 3. [MEDIUM] Weak Protection Against Brute Force OTP Verification
**Vulnerability Type:** Rate Limiting / Race Condition
**Location:** `app/Services/OtpService.php` (Method: `verify`)

**Description:**
The `verify()` method increments the attempt counter (`$otp->increment('attempts')`) on each failed guess. However, because there is no explicit rate limiting on the `/verify-email-otp` or password reset endpoints, and the increment is not row-locked, a fast concurrent brute force attack (e.g., trying hundreds of 6-digit codes simultaneously) could exploit a race condition and bypass the 5-attempt limit before the database processes the increments.

**Solution:**
1. Use a database transaction with a lock (`lockForUpdate()`) when fetching the OTP to verify, or
2. Apply strict rate limiting (`throttle:5,1`) to the `/verify-email-otp` and `/reset-password` routes to prevent fast concurrent requests.

---

### 4. [LOW] Confusing Middleware Definitions
**Vulnerability Type:** Architecture / Maintainability
**Location:** `app/Http/Middleware/EnsureCatalogEditorRole.php`

**Description:**
The middleware `EnsureCatalogEditorRole` checks if the user's role is `ADMIN_SLUG`, `PARTNER_SLUG`, or `USER_SLUG`. While this aligns with the business rule that users can manage their own (capped) records, the name `CatalogEditorRole` is highly misleading and implies elevated privileges. This creates a trap for future developers who might assume a route protected by `catalog_editor` is safe from normal users.

**Solution:**
Rename the middleware to accurately reflect its purpose (e.g., `EnsureActiveCatalogUserRole`) or remove role-based middleware from these routes entirely and rely on centralized Laravel Authorization Policies to control access at the resource level.

---

## Conclusion
The application is generally well-structured and properly utilizes Laravel's core security features like FormRequests and ORM bindings. However, the BOLA/IDOR vulnerability in the catalog controllers exposes the system to catastrophic data loss. Addressing the authorization logic and applying rate limiters to authentication endpoints should be the immediate priority before production release.