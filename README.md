# 🚀 Hushstack Admin Portal API

Enterprise-grade Laravel 10 backend API implementing **Clean Architecture**, **OOAD principles**, and comprehensive **RBAC (Role-Based Access Control)** with 41 granular permissions.  

Production-ready REST API handling authentication, user management, catalog systems (departments, categories, brands, products), project management, and advanced permission systems with Laravel Sanctum security.

## 📚 Table of Contents

- [🎯 Overview](#-overview)
- [🏗️ Architecture & Design Patterns](#️-architecture--design-patterns)
- [🧰 Technology Stack](#-technology-stack)
- [✨ Complete Feature List](#-complete-feature-list)
- [🗂️ Project Structure](#️-project-structure)
- [⚙️ Getting Started](#️-getting-started)
- [🔐 Environment Configuration](#-environment-configuration)
- [🛠️ Runtime & Operations](#️-runtime--operations)
- [🧭 Complete API Reference](#-complete-api-reference)
- [🛡️ Security & Compliance](#️-security--compliance)
- [📦 Deployment Guide](#-deployment-guide)

## 🎯 Overview

**Hushstack Admin Portal API** is a production-ready Laravel 10 REST API implementing enterprise software engineering patterns:

### 🔑 Key Capabilities

- **🔐 Authentication & Authorization**: Email/OTP, Google/Microsoft OAuth, Laravel Sanctum tokens
- **👤 User Management**: Profile management, admin user CRUD, role assignment
- **🔑 RBAC System**: 41 hardcoded permissions via PHP Enum, middleware enforcement
- **📦 Catalog Management**: Departments → Categories → Brands → Products hierarchy
- **📁 Project Management**: CRUD with soft deletes, publishing, image uploads
- **👥 Team Management**: Members and positions with public request forms
- **📨 Notifications**: Email queue, Telegram bot integration, activity logging
- **🛡️ Security**: OWASP-compliant with ownership validation, rate limiting, CORS

### 🎯 Target Use Cases

- Multi-tenant admin portals with role-based access
- E-commerce catalog systems with ownership enforcement  
- Project portfolio management with public/private visibility
- Team directory with member request workflows

## 🏗️ Architecture & Design Patterns

### 🧩 Clean Architecture (Layered)

```
┌─────────────────────────────────────────────────────────┐
│  Presentation Layer (Controllers, Requests, Resources)   │
├─────────────────────────────────────────────────────────┤
│  Application Layer (DTOs, Services, Interfaces)          │
├─────────────────────────────────────────────────────────┤
│  Domain Layer (Models, Enums, Exceptions)                │
├─────────────────────────────────────────────────────────┤
│  Infrastructure Layer (Repositories, Cache, Storage)     │
└─────────────────────────────────────────────────────────┘
```

### 🎨 Design Patterns Used

| Pattern | Implementation |
|---------|---------------|
| **Repository Pattern** | `PermissionRepository`, `RolePermissionRepository` - data access abstraction |
| **Service Layer** | `PermissionService`, `RolePermissionService` - business logic encapsulation |
| **DTO Pattern** | `AssignPermissionsData`, `CreatePermissionData` - type-safe data transfer |
| **Enum Pattern** | `Permission` enum - 41 hardcoded permissions with labels/descriptions |
| **Middleware** | `CheckPermission`, `EnsureAdminRole` - cross-cutting concerns |
| **Factory Pattern** | `PermissionData::fromRequest()` - object creation |
| **Observer Pattern** | Model events for activity logging |

### 📐 OOAD Principles

- **Single Responsibility**: Controllers delegate to services, services to repositories
- **Open/Closed**: Extensible via interfaces, closed for modification
- **Liskov Substitution**: Repository interfaces allow implementation swaps
- **Interface Segregation**: Focused contracts (`PermissionServiceInterface`)
- **Dependency Inversion**: Depend on abstractions, not concretions

## 🧰 Technology Stack

### 🖥️ Core Framework
- **PHP** `^8.1` with readonly classes, enums, typed properties
- **Laravel** `^10.10` - MVC framework
- **Laravel Sanctum** - API token authentication
- **Laravel Socialite** - OAuth integration

### 🔐 Authentication & Security
- **Laravel Sanctum** - Token-based API auth
- **Laravel Socialite** - Google & Microsoft OAuth
- **socialiteproviders/microsoft** - Azure AD integration
- **Custom Middleware** - Permission & role enforcement

### 📦 Data & Storage
- **PostgreSQL/MySQL** - Primary database
- **Redis** - Caching (permissions, roles)
- **Cloudflare R2** - S3-compatible object storage for images

### 📨 Async Processing
- **Laravel Queue** - Database/Redis drivers
- **Job Classes** - `SendContactNotificationJob`, `ProcessAccountDeletionJob`

### 🛠️ DevOps
- **GitHub Actions** - CI/CD with Telegram notifications
- **Laravel Telescope** - Debug assistant (local)
- **Laravel Log Viewer** - Admin log inspection

## ✨ Complete Feature List

### 🔐 Authentication & Authorization

| Feature | Description |
|---------|-------------|
| **Email/Password Auth** | Register, login, logout with bcrypt hashing |
| **OTP Verification** | 6-digit email OTP (10-min expiry, 3-attempt limit) |
| **Password Recovery** | Forgot/reset password via email OTP |
| **Social Login** | Google OAuth 2.0, Microsoft Azure AD |
| **Token Auth** | Laravel Sanctum Bearer tokens |
| **RBAC** | 4 roles: Super Admin, Admin, Partner, User |
| **41 Permissions** | Hardcoded PHP Enum (users.view, projects.create, etc.) |
| **Permission Middleware** | `permission:users.view` or `any_permission:a,b` |
| **Rate Limiting** | Throttle: login (5/1min), OTP (3/1min), reset (3/1min) |

### 👤 User & Profile Management

| Feature | Description |
|---------|-------------|
| **Profile Header** | Bio, social links, profile/cover images (R2 upload) |
| **Personal Info** | First/last name, phone, username (7-day update policy) |
| **Address** | Country, city, postal code, tax ID, address |
| **Image Uploads** | Cloudflare R2/S3-compatible storage |
| **Username Policy** | Rate-limited: 1 update per 7 days |

### 🏢 Admin & User Management

| Feature | Description |
|---------|-------------|
| **User List** | Paginated with role filtering |
| **User Search** | Search by name, email, username |
| **User CRUD** | View details, delete with activity log |
| **Role Management** | Create/update/delete roles with slug validation |
| **Role Assignment** | Assign roles with Telegram notifications |
| **Activity Log** | Track admin actions with IP |
| **User Requests** | View pending member join requests |

### 🔑 Advanced RBAC Permission System

| Feature | Description |
|---------|-------------|
| **Permission Enum** | 41 permissions with labels & descriptions |
| **Assign to Role** | `POST /admin/roles/{id}/permissions` |
| **Revoke from Role** | `DELETE /admin/roles/{id}/permissions` (body: permission_ids) |
| **Revoke All** | `DELETE /admin/roles/{id}/permissions/all` |
| **Auto Sync** | Enum syncs to DB on migration |
| **Caching** | Redis caching for permission lookups |
| **Protected Roles** | Super Admin permissions cannot be modified |

**41 Available Permissions:**
```
users.view, users.create, users.update, users.delete, users.manage
roles.view, roles.create, roles.update, roles.delete, roles.manage
permissions.view, permissions.create, permissions.update, permissions.delete, permissions.assign, permissions.revoke
projects.view, projects.create, projects.update, projects.delete, projects.manage, projects.publish
positions.view, positions.create, positions.update, positions.delete
members.view, members.create, members.update, members.delete
departments.view, departments.create, departments.update, departments.delete
categories.view, categories.create, categories.update, categories.delete
brands.view, brands.create, brands.update, brands.delete
products.view, products.create, products.update, products.delete
logs.view, logs.manage
```

### 📦 Catalog Management (Ownership-Enforced)

| Entity | Features |
|--------|----------|
| **Departments** | CRUD, image upload, user ownership |
| **Categories** | CRUD, department relationship, ownership |
| **Brands** | CRUD, image upload, ownership |
| **Products** | CRUD, SKU validation, stock tracking, images |
| **Security** | IDOR/BOLA protection - users only see own data |
| **Limits** | Max 5 items per type for User role |

### 📁 Project Management

| Feature | Description |
|---------|-------------|
| **Project CRUD** | Create, read, update, delete |
| **Soft Deletes** | Restore (`POST /restore`) and force delete |
| **Publishing** | Public endpoint shows only `is_published=true` |
| **Images** | Cover + gallery via R2 |
| **Search** | Filter by title, published status |
| **Rate Limit** | 60 requests/minute |

### 👥 Member & Position Management

| Feature | Description |
|---------|-------------|
| **Positions** | Job title CRUD |
| **Members** | Team member CRUD with images |
| **Public Request** | `/member/request` form with admin notification |

### 📨 Contact & Notifications

| Feature | Description |
|---------|-------------|
| **Contact Form** | Public endpoint with validation |
| **Email Notifications** | Admin alert + user confirmation (async queue) |
| **Telegram Bot** | Optional chat notifications |
| **Async Delivery** | Queue-based processing |

### 🗑️ Account Lifecycle

| Feature | Description |
|---------|-------------|
| **Soft Delete** | Delayed deletion (configurable) |
| **Warning Email** | Pre-deletion notification |
| **Token Cleanup** | Revoke all Sanctum tokens |
| **Queue Processing** | Async deletion workflow |

### 🔍 Observability

| Feature | Description |
|---------|-------------|
| **Log Viewer** | Admin-only Laravel log inspection |
| **Activity Log** | All actions tracked with IP, user, timestamp |
| **Error Codes** | Specific codes (e.g., `ASSIGN_PERMISSION_FAILED`) |
| **API Standard** | Consistent JSON: `{status_code, status, message, data, error_code}` |

### 🛡️ Security (OWASP Compliant)

| Standard | Implementation |
|----------|---------------|
| **OWASP A01** | Role/permission middleware, ownership checks |
| **OWASP A03** | FormRequest validation, parameterized queries |
| **OWASP A05** | Environment-based config, CORS restrictions |
| **OWASP A07** | IDOR protection via ownership validation |
| **Rate Limiting** | Route-level throttling |
| **XSS Protection** | Input sanitization, output encoding |
| **SQL Injection** | Eloquent ORM, prepared statements |

### ⚙️ DevOps & CI/CD

| Feature | Description |
|---------|-------------|
| **GitHub Actions** | Telegram alerts on push/merge/branch |
| **Auto Seeding** | `PermissionSeeder` runs on deployment |
| **Queue Workers** | Async job processing |
| **Redis Caching** | Permission/role caching layer |

---

## �️ Project Structure (Clean Architecture)

```
app/
├── Contracts/                      # Interfaces (Repository, Service)
│   ├── Repositories/
│   │   ├── PermissionRepositoryInterface.php
│   │   └── RolePermissionRepositoryInterface.php
│   └── Services/
│       ├── PermissionServiceInterface.php
│       └── RolePermissionServiceInterface.php
├── DTOs/                           # Data Transfer Objects
│   └── Permission/
│       ├── AssignPermissionsData.php
│       ├── CreatePermissionData.php
│       └── UpdatePermissionData.php
├── Enums/                          # PHP Enums
│   └── Permission.php              # 41 hardcoded permissions
├── Exceptions/                     # Domain Exceptions
│   ├── DuplicatePermissionException.php
│   ├── InvalidPermissionException.php
│   ├── PermissionNotAssignedException.php
│   ├── PermissionNotFoundException.php
│   ├── PermissionInUseException.php
│   ├── ProtectedPermissionException.php
│   └── ProtectedRoleException.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── ProfileController.php
│   │   ├── RoleController.php
│   │   ├── RolePermissionController.php
│   │   ├── PermissionController.php
│   │   ├── UserRoleController.php
│   │   ├── Admin/
│   │   │   ├── UserAdminController.php
│   │   │   ├── ProjectController.php
│   │   │   ├── PositionController.php
│   │   │   ├── MemberController.php
│   │   │   ├── UserRequestController.php
│   │   │   ├── UserActivityController.php
│   │   │   └── LogController.php
│   │   ├── BrandController.php
│   │   ├── CategoryController.php
│   │   ├── DepartmentController.php
│   │   ├── ProductController.php
│   │   ├── GoogleAuthController.php
│   │   ├── MicrosoftAuthController.php
│   │   ├── ContactController.php
│   │   ├── AccountController.php
│   │   └── MemberRequestController.php
│   ├── Kernel.php                  # Middleware aliases
│   ├── Middleware/
│   │   ├── CheckPermission.php     # Single permission check
│   │   ├── CheckAnyPermission.php  # Any of multiple permissions
│   │   ├── EnsureAdminRole.php     # Admin/Super Admin role check
│   │   └── EnsureSuperAdminRole.php
│   ├── Requests/                   # FormRequest validation
│   │   ├── Permission/
│   │   │   ├── AssignPermissionRequest.php
│   │   │   ├── RevokePermissionRequest.php
│   │   │   ├── StorePermissionRequest.php
│   │   │   └── UpdatePermissionRequest.php
│   │   ├── Role/
│   │   ├── Profile/
│   │   └── Admin/
│   └── Resources/                  # API response transformers
│       ├── PermissionResource.php
│       ├── RoleResource.php
│       └── RoleWithPermissionsResource.php
├── Jobs/                           # Async queue jobs
│   ├── Admin/
│   │   └── SendRoleAssignmentAlertJob.php
│   ├── ContactNotificationJob.php
│   ├── ProcessAccountDeletionJob.php
│   └── SendDeletionWarningJob.php
├── Mail/                           # Mailable classes
├── Models/                         # Eloquent models
│   ├── Permission.php
│   ├── Role.php                    # Has permissions relationship
│   ├── User.php                    # Has role, permission checks
│   ├── Department.php
│   ├── Category.php
│   ├── Brand.php
│   ├── Product.php
│   ├── Project.php
│   ├── Position.php
│   └── Member.php
├── Providers/
│   └── AppServiceProvider.php      # Binds interfaces to implementations
├── Repositories/                   # Data access layer
│   ├── PermissionRepository.php
│   └── RolePermissionRepository.php
├── Services/                       # Business logic layer
│   ├── PermissionService.php
│   ├── RolePermissionService.php
│   ├── PermissionRegistrar.php   # Syncs enum to DB
│   ├── CacheService.php
│   ├── AuthService.php
│   ├── OtpService.php
│   ├── ProfileService.php
│   ├── ProjectService.php
│   └── ActivityLogger.php
└── Traits/
    └── ApiResponseTrait.php        # Standardized JSON responses

database/
├── migrations/
│   ├── 2026_04_10_000001_create_permissions_table.php
│   ├── 2026_04_10_000002_create_role_permissions_table.php
│   └── 2026_04_10_000003_sync_permissions_to_database.php
└── seeders/
    ├── DatabaseSeeder.php
    ├── PermissionSeeder.php        # Runs PermissionRegistrar
    ├── RoleSeeder.php              # Creates Super Admin role
    └── UserSeeder.php              # Creates Super Admin user

routes/
└── api.php                         # All API routes with middleware

docs/
└── Features/
    ├── feature-permissions-role-management-20260410.md
    ├── permission-testing-guide.md
    └── permission-usage-examples.md

.github/
└── workflows/
    └── telegram-alert.yml          # GitHub Actions with Telegram
```

## ⚙️ Getting Started

### ✅ Prerequisites

- **PHP** `^8.1` with extensions: pdo, pgsql/mysql, mbstring, xml, ctype, json, tokenizer, bcmath, curl
- **Composer** `^2.0`
- **PostgreSQL** `^14` or MySQL `^8.0`
- **Redis** (optional, for caching)
- **Mail Service** (SMTP credentials)
- **Cloudflare R2** or S3-compatible storage (for image uploads)

### 📥 Installation

```bash
# Clone repository
git clone https://github.com/hushstack/Hushstack-Admin-Portal-Laravel.git
cd Hushstack-Admin-Portal-Laravel

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### � Database Setup

```bash
# Run migrations (includes permission auto-sync)
php artisan migrate

# Seed Super Admin role and user
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=UserSeeder
```

**Default Super Admin credentials:**
- Email: `superadmin@gmail.com`
- Password: `Password123@`

### ▶️ Run Application

```bash
# Start development server
php artisan serve

# Start queue worker (required for emails/notifications)
php artisan queue:work

# Or run both in separate terminals
```

### 🧪 Quick Test

```bash
# Get auth token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "superadmin@gmail.com", "password": "Password123@"}'

# Use token for protected endpoints
curl http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## � Complete API Reference

### 🔑 Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/auth/register` | - | Register with email/password |
| `POST` | `/api/auth/login` | - | Login (returns token) |
| `POST` | `/api/auth/verify-email-otp` | - | Verify email with OTP |
| `POST` | `/api/auth/resend-otp` | - | Resend verification OTP |
| `POST` | `/api/auth/forgot-password` | - | Request password reset OTP |
| `POST` | `/api/auth/reset-password` | - | Reset password with OTP |
| `POST` | `/api/auth/logout` | ✅ | Revoke current token |
| `POST` | `/api/auth/change-password` | ✅ | Change password |
| `POST` | `/api/auth/delete-account` | ✅ | Request account deletion |
| `GET` | `/api/auth/google/redirect` | - | Google OAuth redirect |
| `GET` | `/api/auth/google/callback` | - | Google OAuth callback |
| `GET` | `/api/auth/microsoft/redirect` | - | Microsoft OAuth redirect |
| `GET` | `/api/auth/microsoft/callback` | - | Microsoft OAuth callback |

### � Profile (Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/profile/header` | Get profile header info |
| `POST` | `/api/profile/header` | Update profile header |
| `GET` | `/api/profile/personal-info` | Get personal info |
| `POST` | `/api/profile/personal-info` | Update personal info |
| `GET` | `/api/profile/address` | Get address |
| `POST` | `/api/profile/address` | Update address |

### 🏢 Admin (Auth + Admin Role Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/admin/users` | List users (paginated) |
| `GET` | `/api/admin/users/search?q=term` | Search users |
| `GET` | `/api/admin/users/{user}` | View user details |
| `DELETE` | `/api/admin/users/{user}` | Delete user |
| `POST` | `/api/admin/users/{user}/role` | Assign role to user |
| `GET` | `/api/admin/user-requests` | List member requests |
| `GET` | `/api/admin/user-activities` | View activity logs |

### � Roles & Permissions (Auth + Permission Required)

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| `GET` | `/api/admin/roles` | `admin` | List all roles |
| `POST` | `/api/admin/roles` | `admin` | Create role |
| `PUT` | `/api/admin/roles/{role}` | `admin` | Update role |
| `DELETE` | `/api/admin/roles/{role}` | `admin` | Delete role |
| `GET` | `/api/admin/roles/{role}/permissions` | `permission:permissions.view` | List role's permissions |
| `POST` | `/api/admin/roles/{role}/permissions` | `permission:permissions.assign` | Assign permissions to role |
| `DELETE` | `/api/admin/roles/{role}/permissions` | `permission:permissions.revoke` | Revoke specific permissions |
| `DELETE` | `/api/admin/roles/{role}/permissions/all` | `permission:permissions.revoke` | Revoke all permissions |
| `GET` | `/api/admin/permissions` | `permission:permissions.view` | List all permissions |
| `GET` | `/api/admin/permissions/{permission}` | `permission:permissions.view` | View permission details |
| `GET` | `/api/admin/permissions/{permission}/roles` | `permission:permissions.view` | List roles with permission |

### � Catalog Management

**Public Endpoints:**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/departments` | List departments |
| `GET` | `/api/departments/{department}` | View department |
| `GET` | `/api/categories` | List categories |
| `GET` | `/api/categories/{category}` | View category |
| `GET` | `/api/brands` | List brands |
| `GET` | `/api/brands/{brand}` | View brand |
| `GET` | `/api/products` | List products |
| `GET` | `/api/products/{product}` | View product |

**Admin Endpoints (Require `active_catalog_user` middleware):**
| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/admin/departments` | Create department |
| `PUT` | `/api/admin/departments/{department}` | Update department |
| `DELETE` | `/api/admin/departments/{department}` | Delete department |
| `POST` | `/api/admin/categories` | Create category |
| `PUT` | `/api/admin/categories/{category}` | Update category |
| `DELETE` | `/api/admin/categories/{category}` | Delete category |
| `POST` | `/api/admin/brands` | Create brand |
| `PUT` | `/api/admin/brands/{brand}` | Update brand |
| `DELETE` | `/api/admin/brands/{brand}` | Delete brand |
| `POST` | `/api/admin/products` | Create product |
| `PUT` | `/api/admin/products/{product}` | Update product |
| `DELETE` | `/api/admin/products/{product}` | Delete product |

### � Projects (Auth + Admin + Throttle)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/admin/projects` | List projects |
| `POST` | `/api/admin/projects` | Create project |
| `GET` | `/api/admin/projects/{project}` | View project |
| `POST` | `/api/admin/projects/{project}` | Update project (multipart) |
| `DELETE` | `/api/admin/projects/{project}` | Delete project |
| `POST` | `/api/admin/projects/{id}/restore` | Restore soft-deleted |
| `DELETE` | `/api/admin/projects/{id}/force` | Force delete |
| `GET` | `/api/projects` | Public published projects |

### 👥 Positions & Members (Auth + Admin)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/admin/positions` | List positions |
| `POST` | `/api/admin/positions` | Create position |
| `GET` | `/api/admin/positions/{position}` | View position |
| `PUT` | `/api/admin/positions/{position}` | Update position |
| `DELETE` | `/api/admin/positions/{position}` | Delete position |
| `GET` | `/api/admin/members` | List members |
| `POST` | `/api/admin/members` | Create member |
| `GET` | `/api/admin/members/{member}` | View member |
| `PUT` | `/api/admin/members/{member}` | Update member |
| `DELETE` | `/api/admin/members/{member}` | Delete member |
| `POST` | `/api/member/request` | Public member request |

### 📨 Contact

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/contact` | Submit contact form |

### 🔍 Logs (Auth + Admin + Throttle)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/admin/logs` | View log entries |
| `GET` | `/api/admin/logs/files` | List log files |

## 🛠️ Runtime & Operations

### 🧵 Queue Workers (Required)

Features that require queue workers:

- OTP email delivery
- Password reset emails
- Contact form notifications (email + Telegram)
- Role assignment Telegram alerts
- Account deletion workflows
- Profile image processing

**Production:**
```bash
# Using Supervisor
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
numprocs=2
```

### 🌍 CORS Configuration

Production: Restrict `allowed_origins` in `config/cors.php`:

```php
'paths' => ['api/*'],
'allowed_origins' => [
    'https://yourdomain.com',
    'https://admin.yourdomain.com',
],
```

### 💾 Storage

- **R2 Disk**: Profile images, project covers, member photos
- **Local Disk**: Temporary file processing, logs
- **Public URL**: Ensure `R2_URL` is accessible from frontend

---

## 🛡️ Security & Compliance

### OWASP Top 10 Mitigations

| Risk | Mitigation |
|------|-----------|
| **A01: Broken Access Control** | Role/permission middleware (`CheckPermission`, `EnsureAdminRole`), ownership validation on all catalog endpoints |
| **A03: Injection** | Eloquent ORM with parameterized queries, FormRequest validation |
| **A05: Security Misconfiguration** | Environment-based configs, strict CORS, disabled debug in production |
| **A07: IDOR** | Resource ownership checks (`user_id === auth()->id()`) |
| **A09: Security Logging** | ActivityLogger tracks all admin actions with IP |

### Permission System Security

- **Hardcoded Permissions**: Enum prevents tampering
- **Protected Roles**: Super Admin cannot be modified via API
- **Middleware Enforcement**: Route-level permission checks
- **Caching**: Redis with cache invalidation on permission changes

### API Response Security

```json
{
  "status_code": 403,
  "status": "error",
  "message": "Forbidden. You do not have permission to access this resource.",
  "errors": null,
  "error_code": "FORBIDDEN"
}
```

---

## 📦 Deployment Guide

### 🐳 Production Checklist

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` set to production domain
- [ ] Database credentials configured
- [ ] Redis/cache configured
- [ ] Queue workers running (Supervisor/systemd)
- [ ] Mail provider configured
- [ ] R2/storage credentials configured
- [ ] OAuth redirect URIs updated
- [ ] CORS origins restricted
- [ ] SSL certificate installed
- [ ] `TELEGRAM_BOT_TOKEN` set for notifications

### 🚀 Deployment Steps

```bash
# On server
cd /var/www/hushstack-api

# Pull latest
git pull origin develop

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restart queue workers
php artisan queue:restart

# Optimize
php artisan optimize
```

### 📊 Monitoring

- **Logs**: `storage/logs/laravel.log` (accessible via `/api/admin/logs`)
- **Activity**: All admin actions logged with IP and timestamp
- **Failed Jobs**: `php artisan queue:failed` and `php artisan queue:retry`
- **GitHub Actions**: Telegram notifications on deployments

### 🔒 Security Headers

Add to `public/.htaccess` or web server config:

```
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

---

## 📞 Support & Documentation

- **API Testing Guide**: `docs/Features/permission-testing-guide.md`
- **Feature Documentation**: `docs/Features/feature-permissions-role-management-20260410.md`
- **Permission Examples**: `docs/Features/permission-usage-examples.md`

---

## 📝 License

This project is proprietary software. All rights reserved.