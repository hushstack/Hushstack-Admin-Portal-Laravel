# Hushstack Admin Portal API

> **Enterprise-grade Laravel 10 REST API** with Clean Architecture, 62-permission RBAC, and OAuth integration. Built for scale with advanced security and queue-based async processing.

---

## At a Glance

| Metric | Value |
|--------|-------|
| **Framework** | Laravel 10 + PHP 8.1 |
| **Architecture** | Clean Architecture (4-layer) |
| **Permissions** | 62 granular RBAC permissions |
| **API Endpoints** | 80+ secured REST endpoints |
| **Auth Methods** | Email/OTP, Google, Microsoft Azure AD |
| **Storage** | AWS S3 / Cloudflare R2 |
| **Queue System** | Laravel Jobs + Redis |

---

## Key Highlights

- **Zero-trust security**: OWASP-compliant with ownership validation, rate limiting, signed URL verification
- **Version Control System**: Custom implementation for Collections, Branches, Commits
- **Multi-tenant Catalog**: Departments → Categories → Brands → Products with ownership enforcement
- **Async Architecture**: Queue-based email, notifications, and account lifecycle management
- **CI/CD Ready**: GitHub Actions with Telegram alerts

## Table of Contents

- [At a Glance](#at-a-glance)
- [Key Highlights](#key-highlights)
- [Overview](#overview)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Features](#features)
- [Project Structure](#project-structure)
- [Quick Start](#quick-start)
- [API Overview](#api-overview)
- [Deployment](#deployment)
- [License](#license)

## Overview

Production-ready Laravel 10 REST API built with enterprise patterns for multi-tenant admin portals.

**Core Modules:**
- Authentication (Email/OTP, Google, Microsoft OAuth)
- RBAC with 62 permissions
- Catalog Management (Departments, Categories, Brands, Products)
- Version Control (Collections, Branches, Commits)
- Project Management with soft deletes
- Team Management (Members, Positions)

## Architecture

**Clean Architecture (4-Layer):**
```
Presentation → Application → Domain → Infrastructure
```

**Patterns:** Repository | Service Layer | DTO | Enum | Middleware | Factory | Observer

**OOAD Principles:** SOLID - Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion

## Tech Stack

| Category | Technologies |
|----------|-------------|
| **Backend** | PHP 8.1, Laravel 10, Laravel Sanctum |
| **Auth** | OAuth (Google, Microsoft Azure AD), OTP |
| **Database** | PostgreSQL/MySQL, Redis |
| **Storage** | AWS S3 / Cloudflare R2 |
| **Queue** | Laravel Jobs, Async Processing |
| **DevOps** | GitHub Actions, Telegram CI/CD alerts |

## Features

**Authentication & Security**
- Multi-provider OAuth (Google, Microsoft Azure AD)
- OTP-based email verification & password recovery
- 62-permission RBAC with middleware enforcement
- OWASP-compliant (A01, A03, A05, A07)

**Core Modules**
- **User Management**: Profile, addresses, image uploads to S3/R2
- **Catalog System**: Departments → Categories → Brands → Products with ownership
- **Version Control**: Collections, Branches, Commits
- **Project Management**: Soft deletes, publishing, gallery uploads
- **Team System**: Members, Positions, public request forms

**Infrastructure**
- Queue-based async processing (email, notifications)
- Redis caching for permissions
- GitHub Actions CI/CD with Telegram alerts

## Project Structure

```
app/
├── Contracts/          # Repository & Service Interfaces
├── DTOs/               # Data Transfer Objects
├── Enums/              # PHP 8.1 Enums (62 permissions)
├── Exceptions/         # Domain Exceptions
├── Http/               # Controllers, Middleware, Requests, Resources
├── Jobs/               # Async Queue Jobs
├── Models/             # Eloquent Models
├── Repositories/       # Data Access Layer
├── Services/           # Business Logic Layer
└── Traits/             # Reusable Traits
```

## Quick Start

```bash
# Install & setup
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=UserSeeder

# Run
php artisan serve
php artisan queue:work
```

**Default Admin:** `superadmin@gmail.com` / `Password123@`

## API Overview

80+ REST endpoints across modules:

| Module | Endpoints |
|--------|-----------|
| **Auth** | `/api/auth/*` - Login, register, OAuth, OTP |
| **Profile** | `/api/profile/*` - Header, personal info, address |
| **Admin** | `/api/admin/*` - Users, roles, permissions, logs |
| **Catalog** | `/api/departments`, `/api/categories`, `/api/brands`, `/api/products` |
| **Version Control** | `/api/collections`, `/api/branches`, `/api/commits` |
| **Projects** | `/api/admin/projects`, `/api/projects-public` |
| **Team** | `/api/admin/positions`, `/api/admin/members` |

See `routes/api.php` for complete endpoint definitions.

## Deployment

```bash
# Production deploy
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

**Production Checklist:**
- `APP_ENV=production`, `APP_DEBUG=false`
- Queue workers (Supervisor)
- Redis/cache configured
- S3/R2 storage credentials
- OAuth redirect URIs updated

## License

Proprietary software. All rights reserved.