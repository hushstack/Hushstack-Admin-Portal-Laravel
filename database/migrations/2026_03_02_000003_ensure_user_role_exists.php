<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $exists = DB::table('roles')->where('slug', 'user')->exists();
        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Default user role',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // keep role data intact
    }
};