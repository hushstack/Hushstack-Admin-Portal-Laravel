# CacheWraith Implementation Notes

## Main Files

- `database/migrations/2026_04_26_000001_create_alerts_table.php`
- `app/Models/Alert.php`
- `app/Http/Middleware/CacheWraithAgentAuth.php`
- `app/Http/Requests/Alert/StoreAlertRequest.php`
- `app/Http/Requests/Admin/Alert/IndexAlertRequest.php`
- `app/Http/Resources/AlertResource.php`
- `app/Services/AlertIngestionService.php`
- `app/Services/AlertManagementService.php`
- `app/Http/Controllers/Api/AlertController.php`
- `app/Http/Controllers/Api/Admin/AlertController.php`
- `app/Enums/Permission.php`
- `database/migrations/2026_04_26_000002_sync_alert_permissions.php`
- `routes/api.php`
- `tests/Feature/AlertApiTest.php`

## Service Layer

Alert logic is split into two services.

### AlertIngestionService

Handles:

- agent alert ingestion
- fingerprint generation
- duplicate open alert detection
- occurrence count incrementing
- first/last seen timestamps

### AlertManagementService

Handles:

- admin list filtering
- alert show loading
- acknowledge
- resolve
- reopen
- delete

Controllers stay thin and only coordinate:

- request validation
- service calls
- API responses

## Security Notes

- The ingestion route does not authenticate as a user.
- The agent token is compared with `hash_equals`.
- Request headers are not stored.
- Full invalid payloads are not manually logged by alert code.
- Message, URL, and path fields have size limits.
- Admin APIs require both Sanctum auth and `super-admin` role.
- The ingestion endpoint is rate limited.

## Routes

Agent route:

```php
Route::post('/alerts', [AlertController::class, 'store'])
    ->middleware(['cachewraith.agent', 'throttle:cachewraith-alerts']);
```

Admin routes:

```php
Route::middleware(['auth:sanctum', 'super_admin'])->prefix('admin')->group(function () {
    Route::middleware('throttle:60,1')->group(function () {
        Route::middleware('permission:' . Permission::ALERTS_VIEW->value)->group(function () {
            Route::get('alerts', [AdminAlertController::class, 'index']);
            Route::get('alerts/{alert}', [AdminAlertController::class, 'show']);
        });

        Route::middleware('permission:' . Permission::ALERTS_ACKNOWLEDGE->value)
            ->post('alerts/{alert}/acknowledge', [AdminAlertController::class, 'acknowledge']);

        Route::middleware('permission:' . Permission::ALERTS_RESOLVE->value)
            ->post('alerts/{alert}/resolve', [AdminAlertController::class, 'resolve']);

        Route::middleware('permission:' . Permission::ALERTS_REOPEN->value)
            ->post('alerts/{alert}/reopen', [AdminAlertController::class, 'reopen']);

        Route::middleware('permission:' . Permission::ALERTS_DELETE->value)
            ->delete('alerts/{alert}', [AdminAlertController::class, 'destroy']);
    });
});
```

## Permissions

Alert permissions are defined in `App\Enums\Permission`.

```text
alerts.view
alerts.acknowledge
alerts.resolve
alerts.reopen
alerts.delete
```

The permissions are synced through `PermissionRegistrar`.

For existing deployments, the migration `2026_04_26_000002_sync_alert_permissions.php` runs the sync again so the new alert permissions are inserted into the `permissions` table.

You can also sync manually:

```bash
php artisan db:seed --class=PermissionSeeder
```
