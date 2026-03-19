# Gemini CLI Context for Hushstack Admin Portal

This file provides foundational context, architectural guidelines, and project rules for the Hushstack Admin Portal backend service. When working in this repository, always adhere to the principles outlined here.

## 🎯 Project Overview
- **Name:** Hushstack Admin Portal API
- **Stack:** PHP 8.1+, Laravel 10.x, Sanctum, Socialite
- **Purpose:** Production-oriented backend service handling identity, profile management, role-based access control, catalog management, and account lifecycle workflows.
- **Key Infrastructure:** MySQL database, queue workers (database/redis), Cloudflare R2 (S3-compatible) for file uploads, SMTP for emails, optional Telegram alerts.

## 🗂️ Project Structure & Architecture
- **Controllers (`app/Http/Controllers/Api/`):** Keep controllers thin. Always delegate business logic to Services.
- **Services (`app/Services/`):** Contains core business logic (e.g., Auth, OTP, Profile, Uploads, Catalog, Account Deletion).
- **Requests (`app/Http/Requests/`):** Strict validation of all incoming inputs via FormRequests. Never use `$request->all()` without validation.
- **Resources (`app/Http/Resources/`):** API response transformers. Dates like `created_at`/`updated_at` should be serialized as `M d Y` (e.g., `Jan 07 2026`). Foreign keys are generally returned as objects `{ id, name }` instead of raw `_id` fields for clarity.
- **Models (`app/Models/`):** Avoid mass assignment vulnerabilities; use `$fillable` strictly. Eager-load relationships to avoid N+1 queries.
- **Jobs (`app/Jobs/`):** Asynchronous tasks for external communications and background tasks (e.g., Contact emails, Account deletion, Telegram alerts).
- **Documentation (`doc/`):** Consult existing flow documentation for specifics (`admin-guardrails-and-context.md`, `catalog-flow.md`, `payway-payment-flow.md`).

## 🔐 Security & Guardrails
- **Access Control:** Enforce on every privileged route using `auth:sanctum` and role middlewares (`admin`, `catalog_editor`).
- **Data Protection:** Use ORM bindings to prevent injection, sanitize uploads with MIME/size rules (5MB max for images). Hash passwords and encrypt secrets.
- **Routing:** All API routes are prefixed with `/api`. Admin specific routes use `/api/admin`.

## 👥 Roles & Access Rules
- **Roles System:** Default role slug is `user`. Admin slug is `admin`. Partners use `partner`.
- **Admin:** Bypasses ownership checks, manages users, roles, and the entire catalog.
- **Partner:** Can list/show/update/delete only their own catalog records. No creation quotas.
- **User:** Can list/show/update/delete only their own catalog records. Creation is capped at 5 records per entity type.

## 📦 Catalog Flow (Departments, Categories, Brands, Products)
- **Ownership:** Tracked via `user_id` at creation for users and partners.
- **Images:** Multipart `image` uploads stored on R2 via `UploadService::uploadAndReplace`.
- **Slugs & Constraints:** Slugs are auto-generated from `name` if omitted and must be unique per table. Product SKUs must also be unique.

## 🛠️ Testing & Development
- Write Feature tests for all critical flows, API endpoints, and authorization boundaries.
- Run `php artisan test` to execute the test suite (e.g., `php artisan test --filter=CatalogApiTest`).
- Follow Object-Oriented Design patterns, remove duplication via helpers/traits, and maintain clean, predictable responses.

## 🚀 Operations
- Queue workers are heavily relied upon. Ensure `php artisan queue:work` is running for OTP delivery, contact forms, and delayed account deletion processing.
- Ensure Cloudflare R2 storage and Mail configurations are properly set in the `.env` file.