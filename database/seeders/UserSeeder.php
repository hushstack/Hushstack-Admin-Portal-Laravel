<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * User Seeder
 *
 * Creates a Super Admin user for testing permission system.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Get the super-admin role
        $superAdminRole = Role::where('slug', Role::SUPER_ADMIN_SLUG)->first();

        if (!$superAdminRole) {
            $this->command->warn('Super Admin role not found. Please run role seeder first.');
            return;
        }

        // Check if user already exists
        $existingUser = User::where('email', 'superadmin@gmail.com')->first();

        if ($existingUser) {
            $this->command->info('Super Admin user already exists (ID: ' . $existingUser->id . ').');
            return;
        }

        // Create Super Admin user
        $user = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@gmail.com',
            'password' => Hash::make('Password123@'),
            'role_id' => $superAdminRole->id,
            'is_verified' => true,
            'email_verified_at' => now(),
            'phone_number' => null,
        ]);

        $this->command->info('Super Admin user created successfully (ID: ' . $user->id . ')!');
        $this->command->info('Email: superadmin@gmail.com');
        $this->command->info('Password: Password123@');
    }
}
