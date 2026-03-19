<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\TestUsersSeeder::class,
        ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'like', 'testuser%@example.test')
            ->delete();
    }
};
