# Decisions

## Detected Architecture Decisions

- Use Laravel 10 as the backend framework.
- Use PHP 8.1 or newer.
- Use Laravel Sanctum for API token authentication.
- Use Laravel Socialite for OAuth login.
- Use Google OAuth and Microsoft OAuth.
- Use email OTP for email verification and password reset flows.
- Use Laravel Form Requests for validation.
- Use Laravel API Resources for response formatting.
- Use services for business logic.
- Use repositories where the project already has repository classes.
- Use PHP enum `App\Enums\Permission` as a central permission source.
- Use role and permission middleware for access control.
- Use Eloquent models and Laravel migrations for database access/schema.
- Use jobs and mailables for email and async workflows.
- Use S3/R2-compatible storage through the AWS S3 Flysystem adapter.
- Use PHPUnit for tests.
- Use Vite for frontend asset building.
- Use GitHub Actions for Telegram repository alerts.

## Database Choice

`.env.example` defaults to MySQL. README mentions PostgreSQL/MySQL. Final production database choice is unknown / not found yet.

## Auth Choice

Detected auth choices:

- Sanctum API tokens
- Email/password login
- Email OTP verification
- Google OAuth
- Microsoft OAuth
- CLI auth flow

## Folder Structure Choice

The project follows Laravel folders plus extra application folders:

- `Contracts`
- `DTOs`
- `Enums`
- `Repositories`
- `Services`
- `Traits`

## API Style

REST-style JSON APIs are defined mainly in `routes/api.php`. Admin routes are grouped under `/api/admin`. Public routes exist for contact, member request, alert ingestion, and public projects.

## Unknown Decisions

- Production hosting choice is unknown / not found yet.
- Queue driver used in production is unknown / not found yet.
- Cache driver used in production is unknown / not found yet.
- Whether PostgreSQL or MySQL is used in production is unknown / not found yet.
