# Admin Guardrails (Merged)
Combined highlights from `AGENTS.md` and `notes/admin-feature-context.md` for quick reference.

## OWASP & Security
- Enforce access control on admin routes (`auth:sanctum` + `admin` middleware).
- Validate all inputs with FormRequest; guard against injection; use ORM bindings.
- Use Laravel Hash/Encrypt; secure secrets; disable debug in production.
- Avoid mass assignment (`$fillable` only); sanitize uploads; rate-limit sensitive endpoints.
- Log auth/admin actions; monitor anomalies; prefer allowlists for outbound requests (SSRF).

## Performance
- Eager load to avoid N+1; paginate lists (admin included).
- Index foreign keys/frequent columns; cache expensive queries/config where sensible.

## Clean Code
- Keep controllers thin; move logic to Services/Actions.
- Consistent naming for routes/controllers/models; single-responsibility methods.
- Write tests for critical flows and authorization boundaries; avoid duplication.

## Admin Routing Context (as of 2026-03-04)
- Admin middleware alias: `admin` → `App\Http\Middleware\EnsureAdminRole` (checks role slug against `Role::ADMIN_SLUG`).
- Admin routes (all under `/api/admin`, `auth:sanctum` + `admin`):
  - `roles` API resource (no show).
  - `POST users/{user}/role` assign role.
  - Users: list, search, show, delete.
  - User requests list.
- `RoleController` enforces slug uniqueness and blocks deleting roles with users.
- `UserRoleController` assigns `role_id` via validated request.

## Current Models/Defaults
- Default role slug: `user`; admin slug: `admin`.
- `user_requests` table captures contact/member submissions; `type_req` distinguishes flows.
