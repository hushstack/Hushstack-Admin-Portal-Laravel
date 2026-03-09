# Laravel API Full Security Audit Report

## Scope Reviewed
- Reviewed the full Laravel API entry surface in `routes/api.php`, `routes/web.php`, `routes/console.php`, controllers, middleware, request validators, models, services, jobs, config, migrations, resources, and the existing test suite.
- Traced sensitive request-to-sink paths for auth, social login, catalog administration, public contact/member submissions, queued mail delivery, and account deletion.
- Focused on confirmed issues only. Speculative risks were not reported as findings.

## Findings

### [Critical] [C-01] Catalog write endpoints allowed cross-account update and delete by `user` role

**Location:** `app/Http/Controllers/Api/DepartmentController.php:82`, `app/Http/Controllers/Api/CategoryController.php:82`, `app/Http/Controllers/Api/BrandController.php:80`, `app/Http/Controllers/Api/ProductController.php:94`
**Code Path:** `POST/PATCH/DELETE /api/admin/{departments|categories|brands|products}/{id} -> *Controller@update|destroy -> Eloquent model`
**Status:** Confirmed

#### Problem

The catalog write controllers only enforced ownership checks for `partner` role. The `catalog_editor` middleware also allowed `user` role, so any authenticated `user` could update or delete another user's catalog record by guessing or enumerating IDs.

#### Why It Is Vulnerable

Route model binding resolved the target model before authorization. The write path then used a partner-only guard:

```php
if ($this->isPartner($request) && $product->user_id !== $request->user()->id) {
    return $this->errorResponse('Forbidden.', 403);
}

$product->update($data);
```

For `user` role this condition was false, so the write executed against foreign records.

#### Impact

Any authenticated `user` with catalog access could tamper with or delete another tenant's departments, categories, brands, and products. This is direct broken object-level authorization.

#### Vulnerable Code

```php
if ($this->isPartner($request) && $brand->user_id !== $request->user()->id) {
    return $this->errorResponse('Forbidden.', 403);
}

$brand->delete();
```

#### Fix Applied

File: `app/Http/Controllers/Api/Concerns/InteractsWithCatalogOwnership.php`
Why changed: centralize ownership enforcement so both `partner` and `user` roles are treated as tenant-scoped catalog editors.

```php
private function violatesOwnership(Request $request, ?int $ownerId): bool
{
    return $this->enforcesOwnership($request)
        && (int) $ownerId !== (int) $request->user()?->id;
}
```

File: `app/Http/Controllers/Api/ProductController.php`
Why changed: replace partner-only guards with shared ownership checks on read/write paths.

```php
if ($this->violatesOwnership($request, $product->user_id)) {
    return $this->errorResponse('Forbidden.', 403);
}

$product->update($data);
```

File: `app/Http/Controllers/Api/DepartmentController.php`, `app/Http/Controllers/Api/CategoryController.php`, `app/Http/Controllers/Api/BrandController.php`
Why changed: apply the same object-level authorization rule consistently across all catalog resources.

#### Tests Added/Updated

- `tests/Feature/CatalogApiTest.php:94` verifies a `user` cannot update another user's product.
- `tests/Feature/CatalogApiTest.php:111` verifies a `user` cannot delete another user's brand.
- Existing partner ownership regression remains covered by `tests/Feature/CatalogApiTest.php:81`.

### [High] [H-01] Catalog create and update validation accepted foreign tenant references

**Location:** `app/Http/Requests/Category/StoreCategoryRequest.php:20`, `app/Http/Requests/Category/UpdateCategoryRequest.php:29`, `app/Http/Requests/Product/StoreProductRequest.php:25`, `app/Http/Requests/Product/UpdateProductRequest.php:40`
**Code Path:** `POST/PATCH /api/admin/categories|products -> FormRequest validation -> Controller -> Model::create|update`
**Status:** Confirmed

#### Problem

`department_id`, `category_id`, and `brand_id` were validated only with broad `exists(...)` rules. A tenant-scoped user could attach newly created or updated records to another tenant's catalog tree.

#### Why It Is Vulnerable

The original validation only proved the foreign key existed:

```php
'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
```

No ownership or tenant constraint was enforced before persistence.

#### Impact

Attackers could create cross-tenant associations, inherit data from foreign categories or departments, and interfere with delete cascades or catalog integrity across tenants.

#### Vulnerable Code

```php
'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
```

#### Fix Applied

File: `app/Http/Requests/Category/StoreCategoryRequest.php`
Why changed: require `department_id` to belong to the authenticated tenant for `user` and `partner` roles.

```php
private function departmentExistsRule()
{
    $rule = Rule::exists('departments', 'id');
    $user = $this->user();

    if ($user && in_array($user->role?->slug, [Role::PARTNER_SLUG, Role::USER_SLUG], true)) {
        $rule = $rule->where(fn (Builder $query) => $query->where('user_id', $user->id));
    }

    return $rule;
}
```

File: `app/Http/Requests/Product/StoreProductRequest.php`
Why changed: constrain `category_id` and `brand_id` to caller-owned records for tenant-scoped roles while preserving admin access.

```php
'category_id' => ['required', 'integer', $this->catalogOwnerExistsRule('categories')],
'brand_id' => ['nullable', 'integer', $this->catalogOwnerExistsRule('brands')],
```

File: `app/Http/Requests/Category/UpdateCategoryRequest.php`, `app/Http/Requests/Product/UpdateProductRequest.php`
Why changed: enforce the same ownership rule on update paths.

#### Tests Added/Updated

- `tests/Feature/CatalogApiTest.php:159` verifies product creation rejects foreign `category_id` and `brand_id`.
- `tests/Feature/CatalogApiTest.php:188` verifies category creation rejects a foreign `department_id`.
- `tests/Feature/CatalogApiTest.php:32` was updated to reflect the now-correct requirement that a partner can only use partner-owned related records.

### [High] [H-02] Social OAuth callbacks lacked one-time state enforcement and leaked credentials in redirect URLs

**Location:** `app/Services/GoogleAuthService.php:39`, `app/Services/MicrosoftAuthService.php:39`, `app/Http/Controllers/Api/GoogleAuthController.php:52`, `app/Http/Controllers/Api/MicrosoftAuthController.php:53`
**Code Path:** `/api/auth/google|microsoft/redirect -> *AuthService@redirect -> *AuthService@handleCallback -> controller redirect/json response`
**Status:** Confirmed

#### Problem

The social login flow generated a signed `state` payload but did not persist or consume a one-time nonce, so callbacks with missing or replayed state were still accepted. Successful redirects appended the bearer token in the query string, and Microsoft callback failures exposed raw provider error text in API responses.

#### Why It Is Vulnerable

Without one-time state validation, an attacker can replay a previously issued callback or attempt login CSRF/session confusion in browser flows. Query-string bearer tokens are exposed to browser history, intermediary logs, and referrer leakage. Raw provider errors can disclose integration details to clients.

#### Impact

The callback flow could be abused to replay or confuse login completion and leak access tokens or provider-side failure details.

#### Vulnerable Code

```php
$state = $this->decodeState($request->query('state'));
$redirectTo = $this->validateRedirectTo($state['redirect_to'] ?? null);

return redirect()->away($redirectTo . '?' . $query);
```

#### Fix Applied

File: `app/Services/Concerns/HandlesSocialAuthState.php`
Why changed: introduce signed, cache-backed, one-time OAuth state issuance and consumption.

```php
protected function issueState(?string $redirectTo, string $provider): string
{
    $payload = [
        'redirect_to' => $redirectTo,
        'ts' => time(),
        'nonce' => Str::random(40),
        'provider' => $provider,
    ];

    Cache::put($this->stateCacheKey($provider, $payload['nonce']), true, now()->addSeconds($this->stateTtlSeconds()));

    return $this->encodeState($payload);
}
```

File: `app/Services/GoogleAuthService.php`, `app/Services/MicrosoftAuthService.php`
Why changed: reject missing, expired, or replayed state before provider user resolution.

```php
$state = $this->consumeState($request->query('state'), 'google');

if (!$state) {
    return [
        'success' => false,
        'status' => 422,
        'message' => 'Invalid or expired social login state.',
    ];
}
```

File: `app/Http/Controllers/Api/GoogleAuthController.php`, `app/Http/Controllers/Api/MicrosoftAuthController.php`
Why changed: move browser token delivery to the URL fragment and stop returning raw provider error fields.

```php
$fragment = http_build_query([
    'token' => $payload['token'],
    'user' => json_encode($payload['user']),
]);

return redirect()->away($redirectTo . '#' . $fragment);
```

#### Tests Added/Updated

- `tests/Feature/AuthSecurityTest.php:71` verifies missing social state is rejected.
- `tests/Feature/AuthSecurityTest.php:83` verifies OAuth redirect tokens are delivered in the fragment, not the query string.
- `tests/Feature/AuthSecurityTest.php:113` verifies Microsoft callback responses do not expose raw provider errors.
- `tests/Feature/AuthSecurityTest.php:132` verifies Google OAuth state cannot be replayed.

### [Medium] [M-01] Sensitive public endpoints relied only on the global API throttle

**Location:** `app/Providers/RouteServiceProvider.php:36`, `routes/api.php:20`, `routes/api.php:41`
**Code Path:** `Public auth/contact/member routes -> api middleware -> generic throttle:api`
**Status:** Confirmed

#### Problem

`/api/auth/register`, `/api/auth/login`, `/api/auth/verify-email-otp`, `/api/auth/resend-otp`, `/api/auth/forgot-password`, `/api/contact`, and `/api/member/request` were only protected by the default `throttle:api` limiter of 60 requests per minute.

#### Why It Is Vulnerable

These endpoints trigger authentication decisions, OTP delivery, or queued outbound notifications. The generic limiter is too broad and too permissive for brute force, credential stuffing, OTP abuse, and queue/mail spam scenarios.

#### Impact

Attackers could make high-rate login attempts or generate excessive mail/queue load from public endpoints before hitting a rate limit.

#### Vulnerable Code

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

#### Fix Applied

File: `app/Providers/RouteServiceProvider.php`
Why changed: define dedicated limiters keyed by email plus IP for auth and by IP for public submissions.

```php
RateLimiter::for('auth.login', function (Request $request) {
    return Limit::perMinute(5)->by($this->emailThrottleKey($request));
});

RateLimiter::for('public.contact', function (Request $request) {
    return Limit::perMinutes(10, 5)->by($request->ip());
});
```

File: `routes/api.php`
Why changed: bind the named limiters directly to the sensitive public routes.

```php
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth.login');
Route::post('/contact', [ContactController::class, 'send'])->middleware('throttle:public.contact');
```

#### Tests Added/Updated

- `tests/Feature/AuthSecurityTest.php:33` verifies repeated failed logins are throttled.
- `tests/Feature/AuthSecurityTest.php:55` verifies public contact submissions are throttled after the configured burst.

### [Medium] [M-02] Contact and member notifications failed open to a hardcoded external mailbox

**Location:** `config/contact.php:4`, `app/Jobs/Contact/SendContactJob.php:28`, `app/Jobs/Member/SendMemberRequestJob.php:28`
**Code Path:** `POST /api/contact|member/request -> service -> queued job -> Mail::to(admin)`
**Status:** Confirmed

#### Problem

If `CONTACT_ADMIN_EMAIL` was not configured, the jobs silently defaulted to a hardcoded Gmail address for admin notifications.

#### Why It Is Vulnerable

A deployment mistake would exfiltrate inbound contact messages and member requests to an unintended external mailbox rather than failing closed.

#### Impact

Personal data and inbound business communications could leak outside the intended organization due to a configuration omission.

#### Vulnerable Code

```php
$adminEmail = config('contact.admin_email', 'hushstack168@gmail.com');
Mail::to($adminEmail)->queue(new AdminContactMail($this->payload));
```

#### Fix Applied

File: `config/contact.php`
Why changed: remove the hardcoded fallback and require explicit env configuration.

```php
'admin_email' => env('CONTACT_ADMIN_EMAIL'),
```

File: `app/Jobs/Contact/SendContactJob.php`
Why changed: fail closed when no admin destination is configured.

```php
$adminEmail = config('contact.admin_email');

if ($adminEmail) {
    Mail::to($adminEmail)->queue(new AdminContactMail($this->payload));
}
```

File: `app/Jobs/Member/SendMemberRequestJob.php`
Why changed: apply the same fail-closed behavior for member request notifications.

#### Tests Added/Updated

- `tests/Feature/AuthSecurityTest.php:55` verifies contact submissions do not queue admin mail when `CONTACT_ADMIN_EMAIL` is unset.

### [Low] [L-01] OTP verification and resend responses exposed account state

**Location:** `app/Http/Controllers/Api/AuthController.php:50`, `app/Http/Controllers/Api/AuthController.php:114`
**Code Path:** `POST /api/auth/verify-email-otp|resend-otp -> AuthController`
**Status:** Confirmed

#### Problem

The public OTP endpoints returned distinguishable responses for unknown users, already verified users, cooldown states, and valid resend cases.

#### Why It Is Vulnerable

An attacker could probe email addresses and learn registration or verification state differences without authentication.

#### Impact

Low-grade account enumeration and state disclosure against the auth surface.

#### Vulnerable Code

```php
if (!$user) {
    return response()->json(['success' => false, 'message' => 'User not found'], 404);
}

return response()->json([
    'success' => true,
    'message' => $ok ? 'OTP resent.' : 'Please wait before resending OTP.',
]);
```

#### Fix Applied

File: `app/Http/Controllers/Api/AuthController.php`
Why changed: return generic OTP verification failure for unknown users and generic resend messaging that does not distinguish account state.

```php
if (!$user || !$this->otp->verify($user, $data['otp'])) {
    return response()->json(['success' => false, 'message' => 'Invalid or expired OTP'], 422);
}

'message' => $ok
    ? 'If the email exists and requires verification, OTP resent.'
    : 'If the email exists and requires verification, a new OTP will be sent when eligible.',
```

#### Tests Added/Updated

- No dedicated regression test was added for this low-severity hardening change.

## Additional Hardening Changes

- `app/Http/Middleware/Authenticate.php:15`
  - Changed the API auth middleware to stop redirecting unauthenticated requests to `route('login')`, which does not exist in this API-only project.
- `app/Http/Controllers/Api/Admin/UserAdminController.php:35`
  - Moved the user-list activity log before the response return so admin list views are actually recorded.
- `.env.example:39`
  - Documented `CONTACT_ADMIN_EMAIL`, OAuth whitelist, Telegram, and R2 variables that are already used by the codebase.

## Tests Run

- `php artisan test`
- Result: 17 tests passed, 68 assertions.

## Required ENV Changes

- `CONTACT_ADMIN_EMAIL`
  - Set this to an owned internal mailbox for admin contact and member-request notifications. There is no hardcoded fallback anymore.
- `FRONTEND_REDIRECT_WHITELIST`
  - Ensure this remains set to the exact browser callback URLs that are allowed to receive social-login redirects.

## Open Questions

- `AuthController::resetPassword()` exists and the README documents `POST /auth/reset-password`, but that route is not currently registered in `routes/api.php`. This was not reported as a security finding because it is functional drift rather than an exploitable flaw, but the forgot-password workflow is incomplete without a reset endpoint.
