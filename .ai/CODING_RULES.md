# Coding Rules

## General Rules

- Scan the project before making changes.
- Do not edit unrelated files.
- Keep changes small and focused.
- Follow existing Laravel patterns.
- Do not create duplicate patterns when a service, request, resource, middleware, or repository already exists.
- Explain changed files after work is complete.
- Add or update tests when changing important behavior.

## Naming Rules

- Use Laravel-style class names.
- Controllers should be named like `SomethingController`.
- Form requests should be named like `StoreSomethingRequest`, `UpdateSomethingRequest`, or action-specific request names.
- API resources should be named like `SomethingResource`.
- Services should be named like `SomethingService`.
- Repositories should be named like `SomethingRepository`.
- Jobs should be named by action, such as `SendContactJob`.
- Migrations should use Laravel timestamp naming.

## File Organization Rules

- API controllers belong in `app/Http/Controllers/Api`.
- Admin API controllers belong in `app/Http/Controllers/Api/Admin`.
- Version-control controllers belong in `app/Http/Controllers/Api/VersionControl`.
- Validation belongs in `app/Http/Requests`.
- Response formatting belongs in `app/Http/Resources`.
- Business logic belongs in `app/Services`.
- Data-access abstractions belong in `app/Repositories` when a repository pattern already exists for that area.
- Models belong in `app/Models`.
- Middleware belongs in `app/Http/Middleware`.
- Migrations belong in `database/migrations`.
- Seeders belong in `database/seeders`.
- Feature tests belong in `tests/Feature`.
- Unit tests belong in `tests/Unit`.

## Style Rules

- Use typed constructor injection where the project already uses it.
- Use Form Request validation for new API input.
- Use API Resources for structured API responses.
- Use existing response helpers such as `ApiResponseTrait` when nearby code uses them.
- Prefer services for business logic that is more than simple CRUD.
- Keep route middleware consistent with nearby routes.
- Use existing permission enum values instead of raw permission strings when possible.
- Do not expose implementation details in user-facing API errors.

## Before Finishing

- Review changed files.
- Run relevant tests when practical.
- Mention tests run or explain why tests were not run.
- Update `.ai/CURRENT_STATE.md`, `.ai/TASKS.md`, or `.ai/AGENT_HANDOFF.md` when the task changes project state.
