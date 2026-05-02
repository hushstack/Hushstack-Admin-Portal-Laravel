# Project Context

## Project Name

Hushstack Admin Portal API.

## Project Purpose

Laravel REST API for an admin portal. The README describes it as an enterprise-grade Laravel 10 API with Clean Architecture, RBAC permissions, OAuth integration, catalog management, project management, team management, alerts, and custom version-control-style modules.

## Tech Stack Detected

- PHP `^8.1`
- Laravel `^10.10`
- Laravel Sanctum for API tokens
- Laravel Socialite for OAuth
- Socialite Microsoft provider
- Guzzle HTTP client
- Flysystem AWS S3 adapter for S3/R2-compatible storage
- PHPUnit 10 for tests
- Laravel Pint for formatting
- Laravel Sail available as a dev dependency
- Vite 5 and Axios in `package.json`
- MySQL default in `.env.example`
- Redis configuration exists in `.env.example`
- GitHub Actions workflow for Telegram alerts

## Main Dependencies Detected

From `composer.json`:

- `laravel/framework`
- `laravel/sanctum`
- `laravel/socialite`
- `socialiteproviders/microsoft`
- `guzzlehttp/guzzle`
- `league/flysystem-aws-s3-v3`
- `doctrine/dbal`
- `phpunit/phpunit`
- `laravel/pint`

From `package.json`:

- `vite`
- `laravel-vite-plugin`
- `axios`

## Main Features Detected

- Email/password registration and login
- Email OTP verification
- Forgot/reset password through OTP
- Google OAuth login
- Microsoft OAuth login
- Sanctum token authentication
- CLI auth flow
- Profile APIs
- Admin user management
- Roles and permissions
- Permission enum and database sync
- User request and user activity APIs
- Catalog APIs for departments, categories, brands, and products
- Project management APIs
- Public published-project endpoint
- Position and member management
- Public member request form/API
- Contact API and queued contact email jobs
- Alert ingestion and admin alert management
- GitHub repository, branch, and commit API integration
- Custom version control entities: collections, branches, commits
- Log viewer endpoints for admins
- File/image upload support through upload services

## Main Users If Obvious

- Super admin users
- Admin users
- Partner users
- Regular users
- Public users for contact/member requests and public projects
- CLI users/devices for CLI auth
- CacheWraith or alert agents for alert ingestion

## What Is Unknown

- Exact production hosting platform is unknown / not found yet.
- Exact production database engine is unknown / not found yet. `.env.example` defaults to MySQL, while README mentions PostgreSQL/MySQL.
- Exact frontend application is unknown / not found yet. This repo mainly contains API code and a few Blade views.
- Current production status is unknown / not found yet.
- Full API contract is unknown / not found yet beyond routes and docs.
