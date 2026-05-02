<?php

use Database\Seeders\GitHubPermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->pretending()) {
            return;
        }

        Artisan::call('db:seed', ['--class' => GitHubPermissionSeeder::class]);
    }

    public function down(): void
    {
        // Keep permission rows intact to avoid removing assigned production permissions.
    }
};
