<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Role Seeder
 *
 * Creates the Super Admin role.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Check if Super Admin role exists by slug
        $existingRole = Role::where('slug', Role::SUPER_ADMIN_SLUG)->first();

        if ($existingRole) {
            $this->command->info('Super Admin role already exists (ID: ' . $existingRole->id . ').');
            return;
        }

        // Get the next available ID to avoid sequence conflict
        $maxId = Role::max('id') ?? 0;
        $nextId = $maxId + 1;

        // Create Super Admin role with explicit ID
        $role = new Role([
            'slug' => Role::SUPER_ADMIN_SLUG,
            'name' => 'Super Admin',
            'description' => 'Full system access with all permissions.',
        ]);
        $role->id = $nextId;
        $role->save();

        // Reset the sequence for PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval('roles_id_seq', (SELECT MAX(id) FROM roles))");
        }

        $this->command->info('Super Admin role created successfully (ID: ' . $role->id . ')!');
    }
}
