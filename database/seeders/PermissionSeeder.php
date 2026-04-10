<?php

namespace Database\Seeders;

use App\Services\PermissionRegistrar;
use Illuminate\Database\Seeder;

/**
 * Permission Seeder
 *
 * Syncs hardcoded enum permissions to database.
 * Run this on every deployment to keep DB in sync with code.
 */
class PermissionSeeder extends Seeder
{
    public function __construct(
        private readonly PermissionRegistrar $registrar
    ) {}

    public function run(): void
    {
        $this->command->info('Syncing permissions from Permission enum...');

        $this->registrar->syncPermissions();

        $status = $this->registrar->getRegistrationStatus();
        $registered = count(array_filter($status));

        $this->command->info("{$registered} permissions synced successfully.");

        // Show unregistered (if any issues)
        $unregistered = array_filter($status, fn ($v) => !$v);
        if (!empty($unregistered)) {
            $this->command->warn('Unregistered permissions: ' . implode(', ', array_keys($unregistered)));
        }
    }
}
