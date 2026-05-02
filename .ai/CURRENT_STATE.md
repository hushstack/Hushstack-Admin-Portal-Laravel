# Current State

## What Currently Exists

- Laravel 10 REST API project.
- README with setup, architecture, tech stack, feature overview, and deployment notes.
- Composer dependencies for Laravel, Sanctum, Socialite, Microsoft OAuth, Guzzle, and S3-compatible storage.
- Vite asset setup with Axios.
- API routes for auth, CLI auth, contact, member requests, alerts, profile, admin, catalog, public projects, GitHub repositories, collections, branches, and commits.
- Web routes for welcome page and CLI auth browser flow.
- Eloquent models for users, roles, permissions, catalog, projects, members, alerts, CLI auth, and version-control entities.
- Migrations and seeders.
- Services, repositories, DTOs, enums, exceptions, jobs, mailables, middleware, requests, and resources.
- Blade views for CLI auth and email templates.
- PHPUnit test setup with feature and unit tests.
- GitHub Actions workflow for Telegram alerts.
- Existing docs under `docs/`.

## What Appears To Work

- Project has install and run instructions in `README.md`.
- Routes and controllers are present for the documented API modules.
- Tests are configured through `phpunit.xml`.
- Vendor dependencies are present in the workspace.
- Existing feature tests suggest several modules have at least some coverage.

## What Is Incomplete

- Exact active task list is unknown / not found yet.
- Current test pass/fail status is unknown / not found yet because tests were not run during this documentation task.
- Production deployment details are only partly documented.
- Full frontend app is not present in this repository.
- README says 62 permissions, while `app/Enums/Permission.php` currently appears to contain 69 enum cases.

## What Is Unknown

- Production hosting platform is unknown / not found yet.
- Production database engine is unknown / not found yet.
- Production queue/cache configuration is unknown / not found yet.
- Current API consumers are unknown / not found yet.
- Current release status is unknown / not found yet.

## Last Scan Summary

Date: 2026-05-02

Scanned:

- Top-level project structure
- `README.md`
- `composer.json`
- `package.json`
- `.env.example`
- `routes/api.php`
- `routes/web.php`
- `phpunit.xml`
- `app/Http`
- `app/Services`
- `app/Enums/Permission.php`
- `database/migrations`
- `database/seeders`
- `resources`
- `tests`
- `.github/workflows`

Summary:

This is a Laravel 10 API/admin portal project with Sanctum auth, OTP, OAuth, RBAC, catalog/project/member/alert/GitHub/version-control modules, service/repository patterns, migrations, seeders, Blade email and CLI auth views, PHPUnit tests, and a Telegram GitHub Actions workflow.

## Next Recommended Step

Run the test suite and record the current result in `.ai/TASKS.md` or `.ai/CURRENT_STATE.md`.
