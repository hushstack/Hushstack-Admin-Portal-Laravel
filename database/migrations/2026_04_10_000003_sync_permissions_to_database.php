<?php

use App\Services\PermissionRegistrar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Sync Hardcoded Permissions to Database
 *
 * This migration automatically syncs all hardcoded permissions from
 * the Permission enum to the database on every deployment.
 *
 * OOAD: Infrastructure layer - ensures database state matches code state
 */
return new class extends Migration
{
    public function up(): void
    {
        $registrar = app(PermissionRegistrar::class);

        Log::info('Migration: Syncing permissions from enum to database...');

        $registrar->syncPermissions();

        $status = $registrar->getRegistrationStatus();
        $registered = count(array_filter($status));
        $total = count($status);

        Log::info("Migration: {$registered}/{$total} permissions synced successfully.");
    }

    public function down(): void
    {
        // Do not remove permissions on rollback to prevent data loss
        // Permissions can be manually cleaned up if needed
        Log::info('Migration rollback: Permissions left intact to prevent data loss.');
    }
};
