# Roadmap

This roadmap is based on the current project scan. It should be adjusted as product priorities become clearer.

## Phase 1: Foundation

- Keep Laravel 10, PHP 8.1, Sanctum, and current folder structure stable.
- Confirm required environment variables for local setup.
- Confirm database engine used in each environment.
- Keep role and permission seeders working.
- Keep `.env.example` updated with placeholder-only values.

## Phase 2: Core Features

- Continue improving authentication, OTP, OAuth, and CLI auth flows.
- Continue improving admin user, role, and permission management.
- Continue improving catalog management for departments, categories, brands, and products.
- Continue improving project, member, position, alert, and GitHub repository modules.
- Keep public endpoints clearly separated from authenticated/admin endpoints.

## Phase 3: Refactor And Quality

- Move repeated controller logic into services when it becomes complex.
- Keep repository usage consistent where repositories already exist.
- Avoid duplicate validation or response patterns.
- Keep API resources consistent.
- Review older docs and align them with current code when needed.
- [NEW] Auto-capture feature requests into `.ai/` directory via workflow - Added 2026-05-02

## Phase 4: Testing And Security

- Expand feature tests for sensitive routes.
- Add tests for permission checks, ownership checks, and rate-limited flows.
- Add tests around OTP, token expiration, CLI auth, alerts, and uploads.
- Run PHPUnit before merging important changes.
- Keep security docs and rules up to date.

## Phase 5: Deployment

- Confirm production deployment process.
- Confirm queue worker setup.
- Confirm cache/Redis setup.
- Confirm S3/R2 storage setup.
- Confirm GitHub Actions and Telegram alert secrets.
- Keep `APP_DEBUG=false` and secrets outside the repository in production.
