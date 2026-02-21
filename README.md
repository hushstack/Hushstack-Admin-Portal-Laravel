# Hushstack Admin Portal API (Laravel 10)

Lightweight REST API for the Hushstack admin portal built on Laravel 10 with Sanctum token auth, OTP email verification, and Socialite-based Google/Microsoft login. Includes profile management, account deletion flow, and a queued contact form that notifies via email and Telegram.

## Features
- Email/OTP signup and login with resend + password reset flows.
- Social login: Google and Microsoft (state carries optional `redirect_to` validated against a whitelist).
- Sanctum bearer tokens for authenticated routes.
- Profile endpoints for header, personal info, and address updates.
- Account deletion request handled asynchronously.
- Contact form dispatches admin notification + user confirmation emails and optional Telegram message.

## Tech Stack
- PHP 8.1+, Laravel 10, Sanctum, Socialite (Google + Microsoft), SocialiteProviders/Microsoft.
- Queues (jobs) for email/Telegram contact notifications.
- Mail + optional Telegram bot integration.

## Setup
1. `composer install`
2. Copy env: `cp .env.example .env` (or set values below) then `php artisan key:generate`
3. Configure DB, mail, and the env vars in the next section.
4. Migrate: `php artisan migrate`
5. Run queues for contact jobs: `php artisan queue:work`
6. Serve: `php artisan serve` (or your preferred stack)

## Environment
Required/custom keys in addition to the defaults:
- `SESSION_LIFETIME=10080` (1 week)
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`
- `MICROSOFT_CLIENT_ID`, `MICROSOFT_CLIENT_SECRET`, `MICROSOFT_REDIRECT_URI`, `MICROSOFT_TENANT=common`
- `FRONTEND_REDIRECT_WHITELIST` (comma-separated exact URLs allowed for social callback redirect_to)
- `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` (optional, enables Telegram contact alerts)
- `CONTACT_ADMIN_EMAIL` (falls back to `hushstack168@gmail.com`)

## API Overview (routes/api.php)
- `POST /auth/register` → create user, send OTP
- `POST /auth/verify-email-otp` → verify email
- `POST /auth/resend-otp`
- `POST /auth/login`
- `POST /auth/forgot-password` → send OTP
- `POST /auth/reset-password`
- `POST /auth/logout` (auth)
- `POST /auth/change-password` (auth)
- `POST /auth/delete-account` (auth) → queue deletion
- `GET /auth/google|microsoft/redirect` and `/callback`
- `POST /contact` → queue emails + Telegram
- `GET/POST /profile/header|personal-info|address` (auth)

## Notes
- Queues: contact notifications rely on the configured queue driver; use `database`/`redis` in production.
- Mail: ensure sender config matches your provider; contact emails are queued.
- Storage: profile pictures/covers use the default filesystem disk; set S3 vars if offloading.
