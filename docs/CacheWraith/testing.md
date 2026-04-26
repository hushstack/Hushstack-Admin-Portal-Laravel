# CacheWraith Alert API Testing

## Run Alert Tests

```bash
php artisan test tests/Feature/AlertApiTest.php
```

## Run Full Test Suite

```bash
php artisan test
```

## Covered Behavior

The alert feature tests cover:

- agent can create alert with valid token
- invalid token returns 401
- validation rejects bad payload
- duplicate fingerprint increments `occurrence_count`
- super-admin can list alerts
- super-admin can view alert detail
- normal user cannot access admin alert endpoints
- super-admin can acknowledge alert
- super-admin can resolve alert
- super-admin can reopen alert
- list endpoint filters work
- alert permissions are synced to the database

## Expected Current Result

```text
Tests: 33 passed
```
