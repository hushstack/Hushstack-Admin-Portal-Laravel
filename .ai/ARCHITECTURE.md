# Architecture

## Detected Folder Structure

- `app/Console`: Laravel console kernel.
- `app/Contracts`: service interfaces.
- `app/DTOs`: data transfer objects, currently focused on permissions.
- `app/Enums`: enum definitions, including permissions.
- `app/Exceptions`: custom domain exceptions and Laravel handler.
- `app/Http/Controllers`: API and web controllers.
- `app/Http/Middleware`: auth, role, permission, catalog ownership, and agent auth middleware.
- `app/Http/Requests`: form request validation classes.
- `app/Http/Resources`: API response resource classes.
- `app/Jobs`: queued jobs for contact, member request, and account lifecycle flows.
- `app/Mail`: mailables.
- `app/Models`: Eloquent models.
- `app/Providers`: Laravel service providers.
- `app/Repositories`: repository classes for data access.
- `app/Services`: business logic services.
- `app/Traits`: reusable response trait.
- `config`: Laravel configuration.
- `database/factories`: model factories.
- `database/migrations`: database schema changes.
- `database/seeders`: seeders for users, roles, permissions, GitHub permissions, and test data.
- `docs`: existing project documentation and security/feature notes.
- `resources`: CSS, JS bootstrap files, Blade CLI auth views, and email templates.
- `routes`: API, web, channel, and console routes.
- `tests/Feature`: API and feature tests.
- `tests/Unit`: unit tests.

## Backend Structure

The backend is a Laravel 10 application. Most routes live in `routes/api.php`. Controllers are organized under `app/Http/Controllers/Api`, with admin controllers under `app/Http/Controllers/Api/Admin` and version-control controllers under `app/Http/Controllers/Api/VersionControl`.

The README describes a 4-layer Clean Architecture style:

- Presentation
- Application
- Domain
- Infrastructure

Detected code patterns include controllers, form requests, resources, services, repositories, DTOs, enums, middleware, jobs, mailables, models, and traits.

## Frontend Structure

No full frontend app was detected in this repo. Detected UI files are:

- `resources/views/welcome.blade.php`
- `resources/views/cli-auth/*.blade.php`
- `resources/views/emails/**/*.blade.php`
- `resources/css/app.css`
- `resources/js/app.js`
- `resources/js/bootstrap.js`

Vite is configured through `vite.config.js`, and `package.json` has `dev` and `build` scripts.

## Database Structure

Database structure is managed with Laravel migrations in `database/migrations`.

Detected tables or entities include:

- users
- password reset tokens
- failed jobs
- personal access tokens
- otps
- jobs
- account deletions
- user requests
- user activities
- roles
- permissions
- role permissions
- departments
- categories
- brands
- products
- projects
- collections
- branches
- commits
- positions
- members
- alerts
- CLI login requests

`.env.example` defaults to MySQL. README also mentions PostgreSQL/MySQL.

## Routing And API Structure

Main API routes are in `routes/api.php`.

Detected API areas include:

- `/api/auth/*`
- `/api/cli/auth/*`
- `/api/contact`
- `/api/member/request`
- `/api/alerts`
- `/api/profile/*`
- `/api/admin/*`
- `/api/departments`
- `/api/categories`
- `/api/brands`
- `/api/products`
- `/api/projects-public`
- `/api/collections`
- `/api/branches`
- `/api/commits`

Web routes are in `routes/web.php`.

Detected web routes include:

- `/`
- `/cli-auth/verify`
- `/cli-auth/authorize/{deviceCode}`
- `/cli-auth/requests/{loginRequest}`
- CLI auth approval and Google callback routes

## Important Architecture Patterns

- Laravel controller, request, resource, model, service, repository structure.
- Sanctum token authentication.
- Role and permission middleware.
- Permission enum as a central source of permission names.
- Form request classes for validation.
- API resources for response shaping.
- Services for business logic.
- Repositories for some data access.
- Jobs and mailables for async/email workflows.
- Ownership checks for catalog resources.
- Rate limiting on sensitive routes.

## Where Business Logic Appears To Live

Business logic appears mainly in:

- `app/Services`
- `app/Repositories`
- Some controller methods for simpler CRUD flows
- `app/Models` for Eloquent relationships and model helpers

## Where UI/Components Appear To Live

UI appears mainly in Blade views:

- `resources/views/cli-auth`
- `resources/views/emails`
- `resources/views/welcome.blade.php`

No component-based SPA folder was found.

## Where Tests Appear To Live

Tests live in:

- `tests/Feature`
- `tests/Unit`

PHPUnit configuration is in `phpunit.xml`.
