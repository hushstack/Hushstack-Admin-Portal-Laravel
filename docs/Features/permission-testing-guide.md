# Permission API Testing Guide

## Prerequisites

1. **Super Admin user exists in database**
2. **Migrations have run** (permissions synced to DB)
3. **Postman, curl, or any HTTP client**

## Step-by-Step Testing

---

### Step 1: Get Super Admin Token

First, you need to authenticate as a Super Admin to get an access token.

**Login Endpoint:**
```bash
curl -X POST \
  http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "superadmin@example.com",
    "password": "your_password"
  }'
```

**Expected Response:**
```json
{
  "status_code": 200,
  "status": "ok",
  "message": "Login successful.",
  "data": {
    "token": "1|your_access_token_here",
    "user": { ... }
  }
}
```

**Save the token:** `1|your_access_token_here`

> **Note:** If you don't have a Super Admin user, create one or update an existing user's role to `super-admin` slug.

---

### Step 2: Test List Permissions

**Endpoint:** `GET /api/admin/permissions`

```bash
curl -X GET \
  http://localhost:8000/api/admin/permissions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Expected Response (200 OK):**
```json
{
  "status_code": 200,
  "status": "ok",
  "message": "Permissions loaded.",
  "data": [
    {
      "id": 1,
      "name": "View Users",
      "slug": "users.view",
      "description": "View user profiles and lists",
      "created_at": "2026-04-10 14:30:00",
      "updated_at": "2026-04-10 14:30:00"
    },
    {
      "id": 2,
      "name": "Create Users",
      "slug": "users.create",
      "description": "Create new user accounts",
      "created_at": "2026-04-10 14:30:00",
      "updated_at": "2026-04-10 14:30:00"
    }
    // ... 41 permissions total
  ]
}
```

---

### Step 3: Test Get Single Permission

**Endpoint:** `GET /api/admin/permissions/{id}`

```bash
curl -X GET \
  http://localhost:8000/api/admin/permissions/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "status_code": 200,
  "status": "ok",
  "message": "Permission loaded.",
  "data": {
    "id": 1,
    "name": "View Users",
    "slug": "users.view",
    "description": "View user profiles and lists",
    "roles_count": null,
    "roles": null,
    "created_at": "2026-04-10 14:30:00",
    "updated_at": "2026-04-10 14:30:00"
  }
}
```

---

### Step 4: Test Get Role Permissions

**Endpoint:** `GET /api/admin/roles/{role_id}/permissions`

```bash
# Test with Admin role (ID: 4)
curl -X GET \
  http://localhost:8000/api/admin/roles/4/permissions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Expected Response (initially empty):**
```json
{
  "status_code": 200,
  "status": "ok",
  "message": "Permissions for role Admin loaded.",
  "data": []
}
```

---

### Step 5: Test Assign Permissions to Role

**Endpoint:** `POST /api/admin/roles/{role_id}/permissions`

```bash
curl -X POST \
  http://localhost:8000/api/admin/roles/4/permissions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "permission_ids": [1, 2, 3, 4]
  }'
```

**Expected Response (200 OK):**
```json
{
  "status_code": 200,
  "status": "ok",
  "message": "Permissions assigned to role Admin.",
  "data": {
    "role": {
      "id": 4,
      "name": "Admin",
      "slug": "admin",
      "description": "Administrator role",
      "is_super_admin": false
    },
    "permissions": [
      {
        "id": 1,
        "name": "View Users",
        "slug": "users.view",
        "description": "View user profiles and lists"
      },
      {
        "id": 2,
        "name": "Create Users",
        "slug": "users.create",
        "description": "Create new user accounts"
      }
    ],
    "sync_result": {
      "attached": [1, 2, 3, 4],
      "detached": [],
      "updated": []
    }
  }
}
```

---

### Step 6: Verify Permissions Were Assigned

Run Step 4 again - should now show the assigned permissions.

---

### Step 7: Test Revoke Single Permission

**Endpoint:** `DELETE /api/admin/roles/{role_id}/permissions/{permission_id}`

```bash
curl -X DELETE \
  http://localhost:8000/api/admin/roles/4/permissions/4 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Expected Response (200 OK):**
```json
{
  "status_code": 200,
  "status": "ok",
  "message": "Permission Delete Users revoked from role Admin.",
  "data": null
}
```

---

### Step 8: Test Revoke All Permissions

**Endpoint:** `DELETE /api/admin/roles/{role_id}/permissions`

```bash
curl -X DELETE \
  http://localhost:8000/api/admin/roles/4/permissions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "status_code": 200,
  "status": "ok",
  "message": "All 3 permissions revoked from role Admin.",
  "data": null
}
```

---

### Step 9: Test Get Roles by Permission

**Endpoint:** `GET /api/admin/permissions/{permission_id}/roles`

```bash
curl -X GET \
  http://localhost:8000/api/admin/permissions/1/roles \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "status_code": 200,
  "status": "ok",
  "message": "Roles with permission View Users loaded.",
  "data": [
    {
      "id": 4,
      "name": "Admin",
      "slug": "admin",
      "description": "Administrator role"
    }
  ]
}
```

---

## Error Testing

### Test 1: Non-Super Admin Access (403)

Try with a regular user token:

```bash
curl -X GET \
  http://localhost:8000/api/admin/permissions \
  -H "Authorization: Bearer REGULAR_USER_TOKEN" \
  -H "Accept: application/json"
```

**Expected Response (403 Forbidden):**
```json
{
  "status_code": 403,
  "status": "error",
  "message": "Forbidden. Super Admin access required.",
  "errors": null
}
```

---

### Test 2: Invalid Permission IDs (422)

```bash
curl -X POST \
  http://localhost:8000/api/admin/roles/4/permissions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "permission_ids": [9999, 8888]
  }'
```

**Expected Response (422):**
```json
{
  "status_code": 422,
  "status": "error",
  "message": "One or more permissions do not exist: 9999, 8888",
  "errors": null
}
```

---

### Test 3: Revoke Non-Assigned Permission (422)

```bash
# First revoke all, then try to revoke again
curl -X DELETE \
  http://localhost:8000/api/admin/roles/4/permissions/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "status_code": 422,
  "status": "error",
  "message": "Permission 'View Users' is not assigned to role 'Admin'.",
  "errors": null
}
```

---

## Quick Test Script (Bash)

Save as `test-permissions.sh`:

```bash
#!/bin/bash

BASE_URL="http://localhost:8000/api"
TOKEN="YOUR_TOKEN_HERE"

echo "=== 1. List All Permissions ==="
curl -s "$BASE_URL/admin/permissions" \
  -H "Authorization: Bearer $TOKEN" | jq .

echo -e "\n=== 2. Get Role Permissions (Admin:4) ==="
curl -s "$BASE_URL/admin/roles/4/permissions" \
  -H "Authorization: Bearer $TOKEN" | jq .

echo -e "\n=== 3. Assign Permissions 1,2,3,4 to Admin ==="
curl -s -X POST "$BASE_URL/admin/roles/4/permissions" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"permission_ids": [1, 2, 3, 4]}' | jq .

echo -e "\n=== 4. Verify Assigned Permissions ==="
curl -s "$BASE_URL/admin/roles/4/permissions" \
  -H "Authorization: Bearer $TOKEN" | jq .

echo -e "\n=== 5. Revoke Permission 4 ==="
curl -s -X DELETE "$BASE_URL/admin/roles/4/permissions/4" \
  -H "Authorization: Bearer $TOKEN" | jq .

echo -e "\n=== 6. Get Roles by Permission 1 ==="
curl -s "$BASE_URL/admin/permissions/1/roles" \
  -H "Authorization: Bearer $TOKEN" | jq .

echo -e "\n=== Done ==="
```

Make executable: `chmod +x test-permissions.sh`

Run: `./test-permissions.sh`

---

## Checklist

- [ ] Can login and get token
- [ ] Can list all permissions (41 total)
- [ ] Can get single permission
- [ ] Can assign permissions to role
- [ ] Can verify permissions are assigned
- [ ] Can revoke single permission
- [ ] Can revoke all permissions
- [ ] Can get roles by permission
- [ ] Non-super admin gets 403
- [ ] Invalid permission IDs get 422

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| 401 Unauthorized | Token expired or invalid - re-login |
| 403 Forbidden | User is not Super Admin - check role slug |
| Empty permission list | Run `php artisan migrate` to sync permissions |
| 500 Error | Check Laravel logs: `storage/logs/laravel.log` |
