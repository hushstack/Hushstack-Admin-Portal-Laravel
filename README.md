# Hushstack Admin Portal API

Production-oriented backend service for the Hushstack Admin Portal, built with Laravel 10.  
The API handles identity, profile management, contact communication, and account lifecycle workflows.

## Table of Contents

- [Overview](#overview)
- [Technology Stack](#technology-stack)
- [Core Capabilities](#core-capabilities)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Environment Configuration](#environment-configuration)
- [Runtime & Operations](#runtime--operations)
- [API Route Index](#api-route-index)
- [Security & Behavior Notes](#security--behavior-notes)
- [Deployment Notes](#deployment-notes)

## Overview

This service exposes RESTful endpoints under `/api` for:

- Email/password authentication with OTP verification
- Social authentication (Google and Microsoft)
- Token-based session handling via Laravel Sanctum
- User profile retrieval and updates (header, personal info, address)
- Contact form delivery to admin/user email plus optional Telegram alerts
- Delayed account deletion workflow with asynchronous processing

## Technology Stack

- PHP `^8.1`
- Laravel `^10.10`
- Laravel Sanctum
- Laravel Socialite
- `socialiteproviders/microsoft`
- Queue workers (`database` or `redis` recommended)
- SMTP mail provider
- Cloudflare R2 (S3-compatible) for profile media uploads

## Core Capabilities

### 1) Authentication and Account Access

- Register with email/password and required profile fields
- OTP email verification before login is allowed
- Resend OTP with cooldown enforcement
- Forgot/reset password using OTP
- Change password while authenticated
- Logout by revoking current access token

### 2) Social Login

- Google OAuth callback flow
- Microsoft OAuth callback flow
- Optional `redirect_to` passthrough with strict whitelist validation
- Auto-link existing account by email where applicable

### 3) Profile Management

- Header data (bio, social links, profile picture, cover image)
- Personal information (first name, last name, phone, username, bio)
- Address data (country, city/state, postal code, tax ID, address)
- Username update window policy: once every 7 days

### 4) Contact Workflow

- Public contact endpoint
- Admin notification email
- User confirmation email
- Optional Telegram post to configured chat/thread
- Entire flow executed asynchronously via queue job

### 5) Account Deletion Workflow

- Authenticated delete request endpoint
- Warning email to user
- Delayed deletion dispatch (current implementation: 1 minute)
- Token revocation and user cleanup on execution
- Success email after deletion

## Project Structure

```text
app/
  Http/
    Controllers/Api/        # API controllers
    Requests/               # Request validation classes
    Resources/              # API response transformers
  Jobs/                     # Async jobs (contact, delete account)
  Mail/                     # Mail classes and templates
  Models/                   # Eloquent models
  Services/                 # Business/domain logic
config/                     # Service, queue, storage, contact config
database/
  migrations/               # Schema definitions
routes/
  api.php                   # API routes
```

## Getting Started

### Prerequisites

- PHP 8.1+
- Composer 2+
- Node.js 18+ and npm
- MySQL (or compatible DB configured in `.env`)
- Mail service credentials

### Clone Repository

```bash
git clone <repository-url>
cd Hushstack-Admin-Portal-Laravel
```

### Install Dependencies

```bash
composer install
npm install
```

### Configure Environment

Linux/macOS:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Then generate app key:

```bash
php artisan key:generate
```

### Initialize Database

```bash
php artisan migrate
```

### Run Application

Start API server:

```bash
php artisan serve
```

Start queue worker (required for asynchronous jobs):

```bash
php artisan queue:work
```

Optional asset watcher:

```bash
npm run dev
```

## Environment Configuration

Configure the following variables in `.env`.

### Core

| Variable | Required | Description |
|---|---|---|
| `APP_NAME` | Yes | Application name |
| `APP_ENV` | Yes | Environment (`local`, `staging`, `production`) |
| `APP_KEY` | Yes | Generated via `php artisan key:generate` |
| `APP_URL` | Yes | Public API base URL |
| `SESSION_LIFETIME` | Recommended | Session lifetime in minutes |

### Database

| Variable | Required |
|---|---|
| `DB_CONNECTION` | Yes |
| `DB_HOST` | Yes |
| `DB_PORT` | Yes |
| `DB_DATABASE` | Yes |
| `DB_USERNAME` | Yes |
| `DB_PASSWORD` | Yes |

### Queue

| Variable | Required | Notes |
|---|---|---|
| `QUEUE_CONNECTION` | Yes | Use `database` or `redis` for async processing |

### Mail

| Variable | Required |
|---|---|
| `MAIL_MAILER` | Yes |
| `MAIL_HOST` | Yes |
| `MAIL_PORT` | Yes |
| `MAIL_USERNAME` | Depends on provider |
| `MAIL_PASSWORD` | Depends on provider |
| `MAIL_ENCRYPTION` | Depends on provider |
| `MAIL_FROM_ADDRESS` | Yes |
| `MAIL_FROM_NAME` | Yes |

### OAuth Providers

| Variable | Required | Description |
|---|---|---|
| `GOOGLE_CLIENT_ID` | For Google login | Google OAuth client ID |
| `GOOGLE_CLIENT_SECRET` | For Google login | Google OAuth client secret |
| `GOOGLE_REDIRECT_URI` | For Google login | Google callback URL |
| `MICROSOFT_CLIENT_ID` | For Microsoft login | Microsoft app client ID |
| `MICROSOFT_CLIENT_SECRET` | For Microsoft login | Microsoft app secret |
| `MICROSOFT_REDIRECT_URI` | For Microsoft login | Microsoft callback URL |
| `MICROSOFT_TENANT` | Recommended | Usually `common` |
| `FRONTEND_REDIRECT_WHITELIST` | Recommended | Comma-separated exact allowed redirect URLs |

### Contact and Notifications

| Variable | Required | Description |
|---|---|---|
| `CONTACT_ADMIN_EMAIL` | Recommended | Admin destination for contact notifications |
| `TELEGRAM_BOT_TOKEN` | Optional | Enables Telegram posting |
| `TELEGRAM_CHAT_ID` | Optional | Telegram destination chat ID |

### R2 Storage (Profile Media)

| Variable | Required | Description |
|---|---|---|
| `R2_ACCESS_KEY_ID` | For image upload | R2 access key |
| `R2_SECRET_ACCESS_KEY` | For image upload | R2 secret key |
| `R2_REGION` | Recommended | Default: `auto` |
| `R2_BUCKET` | For image upload | Bucket name |
| `R2_ENDPOINT` | For image upload | S3-compatible endpoint |
| `R2_URL` | For image upload | Public base URL |
| `R2_USE_PATH_STYLE_ENDPOINT` | Optional | Depends on provider setup |

## Runtime & Operations

### Queue-Dependent Features

The following features rely on queue workers:

- OTP email delivery
- Contact email/Telegram delivery
- Delayed account deletion processing
- Account deletion warning/success email delivery

If `QUEUE_CONNECTION=sync`, these execute inline during requests.

### CORS

Current configuration allows all origins (`allowed_origins = ['*']`) for `api/*`.  
Restrict this in production to trusted frontend domains.

### Filesystem

- Profile picture and cover uploads use the `r2` disk.
- Ensure the configured `R2_URL` is publicly reachable by the frontend.

## API Route Index

Base URL prefix: `/api`

### Authentication

- `POST /auth/register`
- `POST /auth/login`
- `POST /auth/verify-email-otp`
- `POST /auth/resend-otp`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`
- `GET /auth/google/redirect`
- `GET /auth/google/callback`
- `GET /auth/microsoft/redirect`
- `GET /auth/microsoft/callback`
- `POST /auth/logout` (auth required)
- `POST /auth/change-password` (auth required)
- `POST /auth/delete-account` (auth required)

### Contact

- `POST /contact`

### Profile (auth required)

- `GET /profile/header`
- `POST /profile/header`
- `GET /profile/personal-info`
- `POST /profile/personal-info`
- `GET /profile/address`
- `POST /profile/address`

## Security & Behavior Notes

- Passwords are hashed before persistence.
- OTP records store hashed codes, with expiry and attempt caps.
- Social login callback redirects are validated against a whitelist.
- Authenticated endpoints are protected by Sanctum (`auth:sanctum`).
- Username update is rate-limited by policy (7-day interval).

## Deployment Notes

- Use `APP_ENV=production` and `APP_DEBUG=false`.
- Configure `QUEUE_CONNECTION` to `database` or `redis` and run persistent workers (Supervisor, systemd, or equivalent).
- Set strict CORS origins in `config/cors.php`.
- Ensure mail credentials are valid and monitored for delivery failures.
- Ensure R2 credentials and bucket permissions are correctly provisioned.
