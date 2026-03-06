# Admin Guardrails & Context (2026-03-06)

Purpose: single reference for security guardrails, performance tips, clean code expectations, and current admin-routing context for the Hushstack Admin Portal (Laravel).

## OWASP / Security
- Enforce access control on every privileged route (`auth:sanctum` + `admin` middleware); validate all inputs with FormRequest.
- Use ORM bindings to prevent injection; avoid mass assignment (`$fillable` only); sanitize uploads with MIME/size rules.
- Use Laravel `Hash`/`Crypt`; keep secrets secure; disable debug in production; rate-limit sensitive endpoints.
- Log auth/admin actions and monitor anomalies; prefer outbound allowlists to reduce SSRF risk.
- Keep dependencies updated; avoid unsigned/untrusted packages.

## Performance
- Eager-load relationships to avoid N+1 queries; paginate admin lists.
- Index foreign keys and frequent filters; cache expensive queries/config where sensible; keep API payloads lean.

## Clean Code
- Keep controllers thin; move business logic to Services/Actions.
- Consistent naming for routes/controllers/models; small single-responsibility methods with predictable responses.
- Write tests for critical flows and authorization boundaries; remove duplication via helpers/traits; follow OOAD patterns.

## Admin Routing & Middleware (current)
- Middleware alias `admin` → `App\Http\Middleware\EnsureAdminRole` (checks `Role::ADMIN_SLUG`).
- Routes under `/api/admin` use `auth:sanctum` + `admin`:
  - `Route::apiResource('roles', RoleController::class)->except(['show'])`
  - `Route::post('users/{user}/role', [UserRoleController::class, 'assign'])`
  - Users: list, search, show, delete
  - User requests: list
- `RoleController` enforces slug uniqueness and blocks deletion when users exist.
- `UserRoleController` assigns `role_id` via validated request; activity/alert hooks log role changes.

## Defaults & Data Notes
- Default role slug: `user`; admin slug: `admin`.
- `user_requests` captures contact/member submissions; `type_req` distinguishes flows.
- Telegram alerts: thread/topic ids are configured in `config/contact.php` (including role-assignment thread id `109`).
