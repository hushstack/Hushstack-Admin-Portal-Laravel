# Feature: Positions and Members (2026-03-20)

## Overview
Implemented a system for managing organizational **Positions** and **Members**. This feature allows admins to define job positions (with images and descriptions) and associate users with those positions as members, including detailed descriptions and skillsets.

## Data Schema

### 1. Positions Table (`positions`)
- `id`: Primary key
- `name`: Position title
- `slug`: Unique slug for URLs
- `image`: URL/path to the position's icon/image (stored on R2)
- `description`: Text description
- `timestamps`: `created_at` and `updated_at`

### 2. Members Table (`members`)
- `id`: Primary key
- `user_id`: Reference to `users.id` (nullable, set null on delete)
- `position_id`: Reference to `positions.id` (cascade on delete)
- `long_description`: Detailed member bio/information
- `skills`: Array of strings (stored as text/JSON)
- `is_published`: Boolean flag for visibility
- `timestamps`: `created_at` and `updated_at`

## Implementation Details

### Models
- `App\Models\Position`: Has many members.
- `App\Models\Member`: Belongs to a user and a position. Uses `array` cast for `skills`.

### Services
- `App\Services\PositionService`: Handles position CRUD and image uploading via `UploadService`.
- `App\Services\MemberService`: Handles member CRUD and relationship loading.

### Controllers (Admin API)
- `App\Http\Controllers\Api\Admin\PositionController`: Restricted to `admin` role.
- `App\Http\Controllers\Api\Admin\MemberController`: Restricted to `admin` role.

### Requests (Validation)
- `App\Http\Requests\Admin\Position\StorePositionRequest`
- `App\Http\Requests\Admin\Position\UpdatePositionRequest`
- `App\Http\Requests\Admin\Member\StoreMemberRequest`
- `App\Http\Requests\Admin\Member\UpdateMemberRequest`

### Resources (API Transformation)
- `App\Http\Resources\PositionResource`: Serializes dates as `M d Y`.
- `App\Http\Resources\MemberResource`: Serializes dates as `M d Y` and loads relationships.

## Security & Authorization
- **Role Control:** Only users with the `admin` role slug can access the CRUD endpoints.
- **Middleware:** Protected by `auth:sanctum` and `admin` middleware.
- **Input Validation:** Strict type-checking and existence checks (e.g., `exists:users,id`, `exists:positions,id`).

## Routes
All routes are prefixed with `/api/admin`:
- `GET /positions`, `POST /positions`, `GET /positions/{id}`, `PUT /positions/{id}`, `DELETE /positions/{id}`
- `GET /members`, `POST /members`, `GET /members/{id}`, `PUT /members/{id}`, `DELETE /members/{id}`

## API Testing & Examples

### 1. Create a Position
**Endpoint:** `POST /api/admin/positions`
**Headers:** `Authorization: Bearer <admin_token>`, `Accept: application/json`
**Body (Multipart/Form-Data if image is included):**
- `name`: "Senior Laravel Developer"
- `description`: "Responsible for backend architecture."
- `image`: (File upload)

**Expected Response (201 Created):**
```json
{
    "status_code": 201,
    "status": "success",
    "message": "Position created.",
    "data": {
        "id": 1,
        "name": "Senior Laravel Developer",
        "slug": "senior-laravel-developer",
        "image": "https://r2-bucket-url.com/positions/images/filename.jpg",
        "description": "Responsible for backend architecture.",
        "created_at": "Mar 20 2026",
        "updated_at": "Mar 20 2026"
    },
    "errors": null
}
```

### 2. Create a Member
**Endpoint:** `POST /api/admin/members`
**Headers:** `Authorization: Bearer <admin_token>`, `Accept: application/json`
**Body (JSON):**
```json
{
    "user_id": 10,
    "position_id": 1,
    "long_description": "Experienced backend developer with 10 years in PHP.",
    "skills": ["PHP", "Laravel", "MySQL", "AWS"],
    "is_published": true
}
```

**Expected Response (201 Created):**
```json
{
    "status_code": 201,
    "status": "success",
    "message": "Member created.",
    "data": {
        "id": 1,
        "user": {
            "id": 10,
            "first_name": "John",
            "last_name": "Doe",
            "username": "johndoe",
            "email": "john@example.com",
            ...
        },
        "position": {
            "id": 1,
            "name": "Senior Laravel Developer",
            "slug": "senior-laravel-developer",
            ...
        },
        "long_description": "Experienced backend developer with 10 years in PHP.",
        "skills": ["PHP", "Laravel", "MySQL", "AWS"],
        "is_published": true,
        "created_at": "Mar 20 2026",
        "updated_at": "Mar 20 2026"
    },
    "errors": null
}
```

### 3. List Members
**Endpoint:** `GET /api/admin/members?per_page=10`
**Expected Response (200 OK):**
```json
{
    "status_code": 200,
    "status": "success",
    "message": "Members loaded.",
    "data": [
        {
            "id": 1,
            "user": { ... },
            "position": { ... },
            "long_description": "...",
            "skills": [...],
            "is_published": true,
            "created_at": "Mar 20 2026",
            "updated_at": "Mar 20 2026"
        }
    ],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "per_page": 10,
        "to": 1,
        "total": 1
    },
    "links": { ... },
    "errors": null
}
```

## Verification
- **Migrations:** `2026_03_20_114219_create_positions_table.php`, `2026_03_20_114224_create_members_table.php`
- **Tests:** `tests/Feature/AdminMemberApiTest.php` (All 3 tests passed).
