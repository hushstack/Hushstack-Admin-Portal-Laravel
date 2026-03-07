# Catalog Flow & Access Rules (2026-03-06)

## Entities & Ownership
- Departments, Categories, Brands, Products each have `user_id` recorded at create time.
- Ownership is enforced for partners and users: they can list/show/update/delete only their own rows.
- Admins bypass ownership checks.

## Role Access
- Middleware `catalog_editor` (`auth:sanctum`) allows roles: `admin`, `partner`, `user`.
- Routes: `/api/admin/{departments|categories|brands|products}` for CRUD.
- Read routes (`/api/{departments|categories|brands|products}`) now require `auth:sanctum`; results are owner-filtered for partner/user, unrestricted for admin. All foreign keys are returned as objects `{ id, name }` (no raw `_id` fields) for clarity in clients.

## Create Limits (role: user)
- Per-entity cap: 5 records per user. On create, a 403 is returned if the quota is exceeded.
- Partners/admins have no cap.

## Image Handling
- Create/Update accepts multipart `image` (max 5MB) for all catalog entities.
- Files stored on R2 via `UploadService::uploadAndReplace`, replacing prior image when provided.

## Slugs & Uniqueness
- Slug is auto-generated from `name` when omitted.
- Slug uniqueness is enforced per table; product SKU must be unique.

## Date Serialization
- API resources format `created_at`/`updated_at` as `M d Y` (e.g., `Jan 07 2026`).

## Tests
- `php artisan test --filter=CatalogApiTest` covers role gates, quotas, ownership, public listing, and product creation.
